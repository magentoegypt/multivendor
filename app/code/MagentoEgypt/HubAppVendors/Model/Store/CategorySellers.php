<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Sellers with a listable product in a category or any of its children (hmStores category_id filter).
 *
 * Reads the category assignments (catalog_category_product) of the category and
 * its descendants by path, so the answer does not depend on the category's
 * is_anchor setting, and only over products already known to be listable — the
 * gate stays the storefront's. Two queries.
 */
class CategorySellers
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array<int, int[]> $listableByVendor vendor id => listable product ids
     * @return int[] ids of the vendors having at least one of those products in the category tree
     */
    public function vendorIds(int $categoryId, array $listableByVendor): array
    {
        $owner = [];
        foreach ($listableByVendor as $vendorId => $productIds) {
            foreach ($productIds as $productId) {
                $owner[(int) $productId] = (int) $vendorId;
            }
        }
        if ($categoryId < 1 || !$owner) {
            return [];
        }

        try {
            $connection = $this->resource->getConnection();
            $categoryTable = $this->resource->getTableName('catalog_category_entity');
            $path = (string) $connection->fetchOne(
                $connection->select()->from($categoryTable, ['path'])->where('entity_id = ?', $categoryId)
            );
            if ($path === '') {
                return [];
            }

            $productIds = $connection->fetchCol(
                $connection->select()
                    ->distinct()
                    ->from(['ccp' => $this->resource->getTableName('catalog_category_product')], ['product_id'])
                    ->join(['cce' => $categoryTable], 'cce.entity_id = ccp.category_id', [])
                    ->where(
                        $connection->quoteInto('cce.entity_id = ?', $categoryId)
                        . ' OR ' . $connection->quoteInto('cce.path LIKE ?', $path . '/%')
                    )
                    ->where('ccp.product_id IN (?)', array_keys($owner))
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: category sellers unavailable: ' . $e->getMessage());

            return [];
        }

        $vendors = [];
        foreach ($productIds as $productId) {
            $vendorId = $owner[(int) $productId] ?? null;
            if ($vendorId !== null) {
                $vendors[$vendorId] = $vendorId;
            }
        }

        return array_values($vendors);
    }
}
