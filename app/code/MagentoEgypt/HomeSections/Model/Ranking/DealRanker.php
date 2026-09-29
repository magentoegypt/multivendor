<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Model\Ranking;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * "Today's Deals": products whose special price is live today, deepest
 * percentage discount first. Extracted from Block\TodaysDeals::
 * getRankedProductIds() and Block\DealsCountdown::getDeadline() so the app
 * (hmDeals, TODAYS_DEALS) and, later, the website read one ranking.
 *
 * Differences from the block, both fixes:
 *   1. STORE SCOPE. The block joins the decimal/datetime tables without a
 *      store filter, so a product with a store-view special price matched on
 *      its default row as well and could rank by the wrong discount. Here the
 *      store view's row wins over the default row (COALESCE), for price,
 *      special price and both dates — how Magento itself resolves them.
 *   2. The countdown is built from the offers actually SHOWN (the ranked page),
 *      not from every live offer, and ends at 23:59:59 store time of the
 *      soonest special_to_date (Magento treats that date as inclusive).
 *
 * "Today" is the store's local date (Asia/Riyadh here), never SQL NOW().
 * Ranking only: the caller applies the storefront visibility gate.
 */
class DealRanker
{
    /** @var array<string, int|null> */
    private array $attributeIds = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly EavConfig $eavConfig,
        private readonly TimezoneInterface $timezone
    ) {
    }

    /**
     * Live offers, deepest percentage first.
     *
     * @return array<int, array{id: int, price: float, special: float, to_date: string|null}>
     */
    public function rank(int $storeId, int $limit, int $offset = 0): array
    {
        $special = $this->attributeId('special_price');
        $price = $this->attributeId('price');
        $from = $this->attributeId('special_from_date');
        $to = $this->attributeId('special_to_date');
        if ($special === null || $price === null || $limit < 1) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $decimal = $this->resource->getTableName('catalog_product_entity_decimal');
        $datetime = $this->resource->getTableName('catalog_product_entity_datetime');
        $storeId = max(0, $storeId);

        //  Only products that have a special price row somewhere in scope: the
        //  whole catalogue never has to be joined eight ways.
        $candidates = $connection->select()
            ->from($decimal, ['entity_id'])
            ->where('attribute_id = ?', $special)
            ->where('store_id IN (?)', [0, $storeId])
            ->where('value IS NOT NULL');

        $specialValue = 'COALESCE(sp_s.value, sp_d.value)';
        $priceValue = 'COALESCE(p_s.value, p_d.value)';
        $fromValue = $from !== null ? 'COALESCE(fd_s.value, fd_d.value)' : 'NULL';
        $toValue = $to !== null ? 'COALESCE(td_s.value, td_d.value)' : 'NULL';

        $select = $connection->select()
            ->from(['e' => $this->resource->getTableName('catalog_product_entity')], ['id' => 'e.entity_id'])
            ->joinLeft(['sp_d' => $decimal], "sp_d.entity_id = e.entity_id AND sp_d.attribute_id = {$special} AND sp_d.store_id = 0", [])
            ->joinLeft(['sp_s' => $decimal], "sp_s.entity_id = e.entity_id AND sp_s.attribute_id = {$special} AND sp_s.store_id = {$storeId}", [])
            ->joinLeft(['p_d' => $decimal], "p_d.entity_id = e.entity_id AND p_d.attribute_id = {$price} AND p_d.store_id = 0", [])
            ->joinLeft(['p_s' => $decimal], "p_s.entity_id = e.entity_id AND p_s.attribute_id = {$price} AND p_s.store_id = {$storeId}", [])
            ->columns([
                'special' => new Expression($specialValue),
                'price' => new Expression($priceValue),
                'to_date' => new Expression($toValue),
            ])
            //  "IN ?" not "IN (?)": the adapter already wraps a quoted Select in
            //  parentheses, and IN ((SELECT …)) is a scalar subquery to MySQL.
            ->where('e.entity_id IN ?', $candidates)
            ->where("{$specialValue} > 0")
            ->where("{$priceValue} > {$specialValue}");

        if ($from !== null) {
            $select->joinLeft(['fd_d' => $datetime], "fd_d.entity_id = e.entity_id AND fd_d.attribute_id = {$from} AND fd_d.store_id = 0", [])
                ->joinLeft(['fd_s' => $datetime], "fd_s.entity_id = e.entity_id AND fd_s.attribute_id = {$from} AND fd_s.store_id = {$storeId}", []);
        }
        if ($to !== null) {
            $select->joinLeft(['td_d' => $datetime], "td_d.entity_id = e.entity_id AND td_d.attribute_id = {$to} AND td_d.store_id = 0", [])
                ->joinLeft(['td_s' => $datetime], "td_s.entity_id = e.entity_id AND td_s.attribute_id = {$to} AND td_s.store_id = {$storeId}", []);
        }

        $today = $this->today($storeId);
        $select->where("{$fromValue} IS NULL OR DATE({$fromValue}) <= ?", $today)
            ->where("{$toValue} IS NULL OR DATE({$toValue}) >= ?", $today)
            ->order(new Expression("(({$priceValue}) - ({$specialValue})) / ({$priceValue}) DESC"))
            ->order('e.entity_id DESC')
            ->limit($limit, max(0, $offset));

        $out = [];
        foreach ($connection->fetchAll($select) as $row) {
            $out[] = [
                'id' => (int) $row['id'],
                'price' => (float) $row['price'],
                'special' => (float) $row['special'],
                'to_date' => $row['to_date'] !== null && $row['to_date'] !== '' ? (string) $row['to_date'] : null,
            ];
        }

        return $out;
    }

    /**
     * End of the soonest-ending offer among $rows (those shown), ISO-8601 with offset.
     *
     * @param array<int, array{to_date?: string|null}> $rows
     */
    public function countdown(array $rows, int $storeId): ?string
    {
        $ends = [];
        foreach ($rows as $row) {
            if (!empty($row['to_date'])) {
                $ends[] = (string) $row['to_date'];
            }
        }
        if (!$ends) {
            return null;
        }
        sort($ends);

        return self::endOfDay($ends[0], $this->storeTimezone($storeId));
    }

    /**
     * 23:59:59 in $timezone on the calendar day of $date, ISO-8601 with offset.
     *
     * special_to_date is stored as a date ("2026-09-09 00:00:00") and is
     * inclusive: the special price is charged all through that day.
     */
    public static function endOfDay(string $date, string $timezone): ?string
    {
        if (!preg_match('/^(\d{4}-\d{2}-\d{2})/', trim($date), $m)) {
            return null;
        }
        try {
            $end = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $m[1] . ' 23:59:59', new \DateTimeZone($timezone));
        } catch (\Throwable $e) {
            return null;
        }

        return $end ? $end->format(\DateTimeInterface::ATOM) : null;
    }

    /**
     * The store's local date, Y-m-d.
     */
    public function today(int $storeId): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone($this->storeTimezone($storeId))))->format('Y-m-d');
    }

    public function storeTimezone(int $storeId): string
    {
        $timezone = (string) $this->timezone->getConfigTimezone(ScopeInterface::SCOPE_STORE, $storeId);

        return $timezone !== '' ? $timezone : 'UTC';
    }

    private function attributeId(string $code): ?int
    {
        if (!array_key_exists($code, $this->attributeIds)) {
            try {
                $id = (int) $this->eavConfig->getAttribute(Product::ENTITY, $code)->getId();
            } catch (\Throwable $e) {
                $id = 0;
            }
            $this->attributeIds[$code] = $id ?: null;
        }

        return $this->attributeIds[$code];
    }
}
