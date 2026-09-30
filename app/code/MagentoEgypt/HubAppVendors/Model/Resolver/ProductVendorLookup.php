<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * catalog_product_entity.vendor_id for products whose loaded data lacks it, in one query.
 *
 * vendor_id is a static column, so any product loaded from the entity table
 * already carries it; this is only the fallback for values built elsewhere.
 */
class ProductVendorLookup
{
    /** @var array<int, int> product id => vendor id */
    private array $known = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param int[] $productIds
     * @return array<int, int> product id => vendor id (0 = Hub Market) for the products that exist
     */
    public function forProducts(array $productIds): array
    {
        $ids = [];
        foreach ($productIds as $productId) {
            $productId = (int) $productId;
            if ($productId > 0) {
                $ids[$productId] = $productId;
            }
        }

        $missing = array_values(array_diff_key($ids, $this->known));
        if ($missing) {
            try {
                $connection = $this->resource->getConnection();
                $rows = $connection->fetchPairs(
                    $connection->select()
                        ->from($this->resource->getTableName('catalog_product_entity'), ['entity_id', 'vendor_id'])
                        ->where('entity_id IN (?)', $missing)
                );
                foreach ($rows as $productId => $vendorId) {
                    $this->known[(int) $productId] = (int) $vendorId;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('HubApp: product sellers unavailable: ' . $e->getMessage());
            }
        }

        return array_intersect_key($this->known, $ids);
    }
}
