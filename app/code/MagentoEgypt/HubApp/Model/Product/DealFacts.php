<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\Catalog\ProductCategories;
use Psr\Log\LoggerInterface;

/**
 * What hmDeals filters and sorts the ranked deals by, per product:
 *
 *   - under / departments: its categories (ProductCategories, the bundle chips' rule);
 *   - created_at: the storefront's "Newest" sort key;
 *   - price: the indexed minimum price for guests on the store's website, the
 *     price sorts of the Home rails (HomeSections CatalogRanker).
 *
 * Read for all ranked deals at once (at most RankedLists::MAX_RANKED) in four
 * queries and kept in the `hubapp` cache with the day's ranking (tag
 * hm_app_catalog); a product that joins the ranking later is read then.
 */
class DealFacts
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductCategories $productCategories,
        private readonly AppCache $appCache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param int[] $productIds
     * @param string $day the ranking's local date, part of the cache key
     * @return array{
     *     products: array<int, array{under: int[], departments: int[], created_at: string, price: float|null}>,
     *     names: array<int, string>
     * }
     */
    public function forProducts(array $productIds, int $storeId, string $day): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds), static fn (int $id): bool => $id > 0)));
        $key = 'deal_facts_' . $storeId . '_' . $day;
        $cached = self::restore($this->appCache->load($key));
        if ($cached !== null && !array_diff($productIds, array_keys($cached['products']))) {
            return $cached;
        }

        $facts = $this->read($productIds, $storeId);
        $this->appCache->save($key, $facts, [Tags::APP_CATALOG], AppCache::MAX_TTL);

        return $facts;
    }

    /**
     * @param int[] $productIds
     * @return array{products: array<int, array<string, mixed>>, names: array<int, string>}
     */
    private function read(array $productIds, int $storeId): array
    {
        $placed = $this->productCategories->forProducts($productIds, $storeId);
        $created = [];
        $prices = [];
        if ($productIds) {
            try {
                $connection = $this->resource->getConnection();
                $created = $connection->fetchPairs(
                    $connection->select()
                        ->from($this->resource->getTableName('catalog_product_entity'), ['entity_id', 'created_at'])
                        ->where('entity_id IN (?)', $productIds)
                );
                $prices = $connection->fetchPairs(
                    $connection->select()
                        ->from($this->resource->getTableName('catalog_product_index_price'), ['entity_id', 'min_price'])
                        ->where('entity_id IN (?)', $productIds)
                        ->where('customer_group_id = ?', 0)
                        ->where('website_id = ?', (int) $this->storeManager->getStore($storeId)->getWebsiteId())
                );
            } catch (\Throwable $e) {
                $this->logger->warning('HubApp: deal sort keys unavailable: ' . $e->getMessage());
            }
        }

        $products = [];
        foreach ($productIds as $id) {
            $products[$id] = [
                'under' => $placed['under'][$id] ?? [],
                'departments' => $placed['departments'][$id] ?? [],
                'created_at' => (string) ($created[$id] ?? ''),
                'price' => isset($prices[$id]) ? (float) $prices[$id] : null,
            ];
        }

        return ['products' => $products, 'names' => $placed['names']];
    }

    /**
     * The cached array with its int keys back (JSON made them strings), or null.
     *
     * @param array<mixed>|null $cached
     * @return array{products: array<int, array<string, mixed>>, names: array<int, string>}|null
     */
    public static function restore(?array $cached): ?array
    {
        if ($cached === null || !isset($cached['products']) || !is_array($cached['products'])) {
            return null;
        }
        $products = [];
        foreach ($cached['products'] as $id => $fact) {
            if (!is_array($fact)) {
                continue;
            }
            $products[(int) $id] = [
                'under' => array_map('intval', (array) ($fact['under'] ?? [])),
                'departments' => array_map('intval', (array) ($fact['departments'] ?? [])),
                'created_at' => (string) ($fact['created_at'] ?? ''),
                'price' => isset($fact['price']) ? (float) $fact['price'] : null,
            ];
        }
        $names = [];
        foreach ((array) ($cached['names'] ?? []) as $id => $name) {
            $names[(int) $id] = (string) $name;
        }

        return ['products' => $products, 'names' => $names];
    }
}
