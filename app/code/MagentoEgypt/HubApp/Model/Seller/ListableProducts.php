<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Seller;

use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\HubApp\Api\ProductListLoaderInterface;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use Psr\Log\LoggerInterface;

/**
 * The products a seller's store page lists, per seller.
 *
 * Same rule as HomeSections NewStores::listableCounts(), the store cards'
 * "N products": the seller's rows in catalog_product_entity (vendor_id is a
 * static column there) passed through the storefront's one visibility gate,
 * ProductListLoaderInterface::sellable() — approved, active seller, enabled and
 * catalog-visible in the store view, not a "select and sell" copy. So a card
 * never promises more products than /shop/<code> shows (TC71: 16 vs 13).
 *
 * Computed for EVERY approved seller of a store view at once (one entity query
 * plus the gate's queries) and kept in the `hubapp` app cache (up to
 * AppCache::MAX_TTL, tagged hm_vendor): every "sold by" and store card needs
 * the count, and the gate is the expensive part on a slow server. Like the
 * rankings, a product saved by the Odoo sync shows up in the counts within the
 * TTL rather than instantly (see Tags::forAppCache()).
 */
class ListableProducts
{
    private const CACHE_KEY_PREFIX = 'seller_listable_';

    /** @var array<int, array<int, int[]>> store id => vendor id => listable product ids */
    private array $byStore = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly ProductListLoaderInterface $productListLoader,
        private readonly SellerDirectory $directory,
        private readonly AppCache $appCache,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param int[] $vendorIds
     * @return array<int, int[]> vendor id => listable product ids, for every id asked
     */
    public function forVendors(array $vendorIds, int $storeId): array
    {
        $ids = [];
        foreach ($vendorIds as $vendorId) {
            $vendorId = (int) $vendorId;
            if ($vendorId > 0) {
                $ids[$vendorId] = $vendorId;
            }
        }

        if (!isset($this->byStore[$storeId])) {
            $this->byStore[$storeId] = $this->loadStore($storeId);
        }

        $out = [];
        foreach ($ids as $vendorId) {
            $out[$vendorId] = $this->byStore[$storeId][$vendorId] ?? [];
        }

        return $out;
    }

    /**
     * @param int[] $vendorIds
     * @return array<int, int> vendor id => number of listable products, for every id asked
     */
    public function counts(array $vendorIds, int $storeId): array
    {
        return array_map('count', $this->forVendors($vendorIds, $storeId));
    }

    /**
     * @return array<int, int[]> vendor id => listable product ids, approved sellers only
     */
    private function loadStore(int $storeId): array
    {
        $key = self::CACHE_KEY_PREFIX . $storeId;
        $cached = $this->appCache->load($key);
        if ($cached !== null) {
            $out = [];
            foreach ($cached as $vendorId => $productIds) {
                if (is_array($productIds)) {
                    $out[(int) $vendorId] = array_map('intval', $productIds);
                }
            }

            return $out;
        }

        $out = $this->query($this->directory->approvedIds(), $storeId);
        if ($out !== null) {
            $this->appCache->save($key, $out, [Tags::VENDOR], AppCache::MAX_TTL);
        }

        return $out ?? [];
    }

    /**
     * @param int[] $vendorIds
     * @return array<int, int[]>|null null when the seller products could not be read
     */
    private function query(array $vendorIds, int $storeId): ?array
    {
        $byVendor = [];
        foreach ($vendorIds as $vendorId) {
            $byVendor[(int) $vendorId] = [];
        }
        if (!$byVendor) {
            return [];
        }

        try {
            $connection = $this->resource->getConnection();
            $owners = $connection->fetchPairs(
                $connection->select()
                    ->from($this->resource->getTableName('catalog_product_entity'), ['entity_id', 'vendor_id'])
                    ->where('vendor_id IN (?)', array_keys($byVendor))
                    ->order('entity_id DESC')
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: seller products unavailable: ' . $e->getMessage());

            return null;
        }
        if (!$owners) {
            return $byVendor;
        }

        foreach ($this->productListLoader->sellable(array_map('intval', array_keys($owners)), $storeId) as $productId) {
            $vendorId = (int) ($owners[$productId] ?? 0);
            if (isset($byVendor[$vendorId])) {
                $byVendor[$vendorId][] = (int) $productId;
            }
        }

        return $byVendor;
    }
}
