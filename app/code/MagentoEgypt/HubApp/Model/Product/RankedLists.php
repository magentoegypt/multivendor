<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Product;

use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\HomeSections\Model\Ranking\BestSellerRanker;
use MagentoEgypt\HomeSections\Model\Ranking\CatalogRanker;
use MagentoEgypt\HomeSections\Model\Ranking\DealRanker;
use MagentoEgypt\HomeSections\Model\Ranking\TopRatedRanker;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use MagentoEgypt\HubApp\Model\Cache\Tags;

/**
 * The gated rankings the app lists: HomeSections rankers + the storefront gate.
 *
 * deals() and bestSellers() back both the Home sections and the paged lists
 * (hmDeals, hmBestSellers); they are computed once up to MAX_RANKED and kept
 * in the `hubapp` cache (tag hm_app_catalog, purged by the cron when orders or
 * the catalogue move, and at local midnight for deals).
 */
class RankedLists
{
    /** Longest list any page can reach (total_count is capped here). */
    public const MAX_RANKED = 200;

    public function __construct(
        private readonly RankedIds $rankedIds,
        private readonly DealRanker $dealRanker,
        private readonly BestSellerRanker $bestSellerRanker,
        private readonly TopRatedRanker $topRatedRanker,
        private readonly CatalogRanker $catalogRanker,
        private readonly AppCache $appCache,
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * Live deals of the store view, gated, deepest discount first (DealRanker rows:
     * a bundle's `special` is the percent paid, `percent_off` is right for every type).
     *
     * @return array<int, array{id: int, type_id?: string, price: float, special: float, percent_off?: float, to_date: string|null}>
     */
    public function deals(int $storeId): array
    {
        $today = $this->dealRanker->today($storeId);
        //  The date is in the key: a new day is a new list even before the purge.
        $key = 'deals_' . $storeId . '_' . $today;
        $cached = $this->appCache->load($key);
        if ($cached !== null) {
            return $cached;
        }

        $rows = $this->rankedIds->topRows(
            fn (int $count, int $offset): array => $this->dealRanker->rank($storeId, $count, $offset),
            self::MAX_RANKED,
            $storeId
        );

        $ttl = AppCache::MAX_TTL;
        try {
            $midnight = new \DateTimeImmutable('tomorrow', new \DateTimeZone($this->dealRanker->storeTimezone($storeId)));
            $ttl = min($ttl, max(1, $midnight->getTimestamp() - time()));
        } catch (\Throwable $e) {
            $ttl = 300;
        }
        $this->appCache->save($key, $rows, [Tags::APP_CATALOG], $ttl);

        return $rows;
    }

    /**
     * Best sellers of the store view, gated, most units first.
     *
     * @return int[]
     */
    public function bestSellers(int $storeId): array
    {
        $key = 'best_' . $storeId;
        $cached = $this->appCache->load($key);
        if ($cached !== null) {
            return array_map('intval', $cached);
        }

        $ids = $this->rankedIds->top(
            fn (int $count, int $offset): array => $this->bestSellerRanker->rank($count, $offset),
            self::MAX_RANKED,
            $storeId
        );
        $this->appCache->save($key, $ids, [Tags::APP_CATALOG], AppCache::MAX_TTL);

        return $ids;
    }

    /**
     * Top rated, gated (Home only; the Home itself is cached).
     *
     * @return int[]
     */
    public function topRated(int $storeId, int $limit): array
    {
        return $this->rankedIds->top(
            fn (int $count, int $offset): array => $this->topRatedRanker->rank($count, $offset),
            $limit,
            $storeId
        );
    }

    /**
     * A category (or the whole catalogue) in a sort, gated (Home only).
     *
     * @return int[]
     */
    public function catalog(int $storeId, ?int $categoryId, ?string $sort, int $limit): array
    {
        return $this->rankedIds->top(
            fn (int $count, int $offset): array => $this->catalogRanker->rank($storeId, $categoryId, $sort, $count, $offset),
            $limit,
            $storeId
        );
    }

    /**
     * Hand-picked SKUs in the given order, gated.
     *
     * @param string[] $skus
     * @return int[]
     */
    public function skus(array $skus, int $storeId, int $limit): array
    {
        $skus = array_values(array_filter(array_map('trim', $skus), static fn (string $s): bool => $s !== ''));
        if (!$skus) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $bySku = [];
        foreach ($connection->fetchPairs(
            $connection->select()
                ->from($this->resource->getTableName('catalog_product_entity'), ['sku', 'entity_id'])
                ->where('sku IN (?)', $skus)
        ) as $sku => $id) {
            $bySku[strtolower((string) $sku)] = (int) $id;
        }

        $ids = [];
        foreach ($skus as $sku) {
            if (isset($bySku[strtolower($sku)])) {
                $ids[] = $bySku[strtolower($sku)];
            }
        }

        return $this->rankedIds->top(
            static fn (int $count, int $offset): array => array_slice($ids, $offset, $count),
            $limit,
            $storeId
        );
    }
}
