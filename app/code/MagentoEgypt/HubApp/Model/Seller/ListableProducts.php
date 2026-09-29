<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Seller;

use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\HubApp\Api\ProductListLoaderInterface;
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
 * One entity query plus the gate's queries per batch of sellers; kept for the
 * rest of the request.
 */
class ListableProducts
{
    /** @var array<int, array<int, int[]>> store id => vendor id => listable product ids */
    private array $byStore = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly ProductListLoaderInterface $productListLoader,
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

        $missing = array_values(array_diff_key($ids, $this->byStore[$storeId] ?? []));
        if ($missing) {
            $this->load($missing, $storeId);
        }

        return array_intersect_key($this->byStore[$storeId] ?? [], $ids);
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
     * @param int[] $vendorIds
     */
    private function load(array $vendorIds, int $storeId): void
    {
        foreach ($vendorIds as $vendorId) {
            $this->byStore[$storeId][$vendorId] = [];
        }

        try {
            $connection = $this->resource->getConnection();
            $owners = $connection->fetchPairs(
                $connection->select()
                    ->from($this->resource->getTableName('catalog_product_entity'), ['entity_id', 'vendor_id'])
                    ->where('vendor_id IN (?)', $vendorIds)
                    ->order('entity_id DESC')
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: seller products unavailable: ' . $e->getMessage());

            return;
        }
        if (!$owners) {
            return;
        }

        foreach ($this->productListLoader->sellable(array_map('intval', array_keys($owners)), $storeId) as $productId) {
            $vendorId = (int) ($owners[$productId] ?? 0);
            if (isset($this->byStore[$storeId][$vendorId])) {
                $this->byStore[$storeId][$vendorId][] = (int) $productId;
            }
        }
    }
}
