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
 *   3. BUNDLES. For `bundle` and `new_bundle` special_price is the PERCENT of
 *      the regular price the customer pays (80 = 20% off; BundleExtend's
 *      BundleSpecialPricePercent keeps it within 0-100, and core's bundle
 *      SpecialPrice reads it that way). The block ranks it as an amount, so a
 *      500 bundle at 90 (10% off) ranked as 82% off. Here a bundle is a deal
 *      when 0 < special_price < 100 and its discount is 100 - special_price.
 *
 * "Today" is the store's local date (Asia/Riyadh here), never SQL NOW().
 * Ranking only: the caller applies the storefront visibility gate.
 */
class DealRanker
{
    /** Product types whose special_price is a percent of the regular price. */
    public const PERCENT_SPECIAL_TYPES = ['bundle', 'new_bundle'];

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
     * `special` is what the attribute holds: the price paid, or for
     * PERCENT_SPECIAL_TYPES the percent of the regular price paid.
     * `percent_off` is the discount either way (percentOff()).
     *
     * @return array<int, array{id: int, type_id: string, price: float, special: float, percent_off: float, to_date: string|null}>
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
        //  Constants, not input: safe to inline.
        $typeList = "('" . implode("', '", self::PERCENT_SPECIAL_TYPES) . "')";
        $percentTypes = "e.type_id IN {$typeList}";
        //  Same rule as percentOff(); for other types the order is the old
        //  (price - special) / price one, scaled to a percent.
        $percentOff = "CASE WHEN {$percentTypes} THEN 100 - ({$specialValue})"
            . " ELSE ((({$priceValue}) - ({$specialValue})) / ({$priceValue})) * 100 END";

        $select = $connection->select()
            ->from(
                ['e' => $this->resource->getTableName('catalog_product_entity')],
                ['id' => 'e.entity_id', 'type_id' => 'e.type_id']
            )
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
            ->where(
                "({$percentTypes} AND {$specialValue} < 100)"
                . " OR (e.type_id NOT IN {$typeList} AND {$priceValue} > {$specialValue})"
            );

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
            ->order(new Expression("{$percentOff} DESC"))
            ->order('e.entity_id DESC')
            ->limit($limit, max(0, $offset));

        $out = [];
        foreach ($connection->fetchAll($select) as $row) {
            $typeId = (string) $row['type_id'];
            $price = (float) $row['price'];
            $special = (float) $row['special'];
            $out[] = [
                'id' => (int) $row['id'],
                'type_id' => $typeId,
                'price' => $price,
                'special' => $special,
                'percent_off' => self::percentOff($typeId, $price, $special) ?? 0.0,
                'to_date' => $row['to_date'] !== null && $row['to_date'] !== '' ? (string) $row['to_date'] : null,
            ];
        }

        return $out;
    }

    /**
     * Discount of an offer in percent (2 decimals), or null when it is not a deal.
     *
     * PERCENT_SPECIAL_TYPES: special_price is the percent paid, so a deal is
     * 0 < special < 100 and the discount is 100 - special (the regular price
     * does not matter; a dynamic-price bundle has none). Other types: a deal is
     * 0 < special < price and the discount is (price - special) / price.
     * The SQL of rank() applies the same rule.
     */
    public static function percentOff(string $typeId, float $price, float $special): ?float
    {
        if ($special <= 0) {
            return null;
        }
        if (in_array($typeId, self::PERCENT_SPECIAL_TYPES, true)) {
            return $special < 100 ? round(100 - $special, 2) : null;
        }

        return $price > $special ? round(($price - $special) / $price * 100, 2) : null;
    }

    /**
     * End of the soonest-ending offer among $rows (those shown), ISO-8601 with offset.
     *
     * @param array<int, array{to_date?: string|null}> $rows
     */
    public function countdown(array $rows, int $storeId): ?string
    {
        return self::soonestEnd(
            array_map(static fn (array $row): ?string => $row['to_date'] ?? null, $rows),
            $this->storeTimezone($storeId)
        );
    }

    /**
     * End of the soonest of $toDates (special_to_date values; empty ones are
     * offers without an end): 23:59:59 in $timezone, ISO-8601 with offset; null
     * when none ends.
     *
     * @param array<int|string, string|null> $toDates
     */
    public static function soonestEnd(array $toDates, string $timezone): ?string
    {
        $ends = [];
        foreach ($toDates as $toDate) {
            if (!empty($toDate)) {
                $ends[] = (string) $toDate;
            }
        }
        if (!$ends) {
            return null;
        }
        sort($ends);

        return self::endOfDay($ends[0], $timezone);
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
