<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Catalog;

use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Where products sit in a store view's category tree, for filter chips.
 *
 * The bundle cards' rule (HomeSections BundleDealBuilder::loadTopCategories()):
 * a product's categories are read from its assignments (catalog_category_product)
 * and their paths, so a product assigned anywhere below a department is in that
 * department, as on an anchor category page. Only paths under the store's own
 * root count. Department names are the ACTIVE top-level categories, in the
 * store view's language, in catalogue order.
 *
 * Direct SQL, two queries for any number of products.
 */
class ProductCategories
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly StoreManagerInterface $storeManager,
        private readonly EavConfig $eavConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param int[] $productIds
     * @return array{
     *     under: array<int, int[]>,
     *     departments: array<int, int[]>,
     *     names: array<int, string>
     * } under: product id => every category it is in or below; departments: product id =>
     *   its active top-level categories; names: active department id => name, catalogue order
     */
    public function forProducts(array $productIds, int $storeId): array
    {
        $empty = ['under' => [], 'departments' => [], 'names' => []];
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds), static fn (int $id): bool => $id > 0)));
        if (!$productIds) {
            return $empty;
        }

        try {
            $rootId = (int) $this->storeManager->getStore($storeId)->getRootCategoryId();
            $connection = $this->resource->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from(['ccp' => $this->resource->getTableName('catalog_category_product')], ['product_id'])
                    ->join(
                        ['ce' => $this->resource->getTableName('catalog_category_entity')],
                        'ce.entity_id = ccp.category_id',
                        ['path']
                    )
                    ->where('ccp.product_id IN (?)', $productIds)
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: product categories unavailable: ' . $e->getMessage());

            return $empty;
        }

        $placed = self::place($rows, $rootId);
        $tops = [];
        foreach ($placed['departments'] as $set) {
            foreach ($set as $top) {
                $tops[$top] = true;
            }
        }
        $names = $this->activeNames(array_keys($tops), $storeId);

        $departments = [];
        foreach ($placed['departments'] as $productId => $set) {
            $departments[$productId] = array_values(array_filter($set, static fn (int $id): bool => isset($names[$id])));
        }

        return ['under' => $placed['under'], 'departments' => $departments, 'names' => $names];
    }

    /**
     * Assignment rows (product_id, path) -> the categories each product is in or
     * below, and its departments (the level under the root). Pure: unit-tested.
     *
     * @param array<int, array{product_id: int|string, path: string}> $rows
     * @return array{under: array<int, int[]>, departments: array<int, int[]>}
     */
    public static function place(array $rows, int $rootId): array
    {
        $under = [];
        $departments = [];
        foreach ($rows as $row) {
            //  path = 1/<root>/<department>/...
            $parts = array_map('intval', explode('/', (string) ($row['path'] ?? '')));
            if (count($parts) < 3 || $parts[1] !== $rootId) {
                continue;
            }
            $productId = (int) $row['product_id'];
            foreach (array_slice($parts, 2) as $categoryId) {
                $under[$productId][$categoryId] = $categoryId;
            }
            $departments[$productId][$parts[2]] = $parts[2];
        }

        return [
            'under' => array_map('array_values', $under),
            'departments' => array_map('array_values', $departments),
        ];
    }

    /**
     * @param int[] $ids
     * @return array<int, string> id => name of the active ones, catalogue order
     */
    private function activeNames(array $ids, int $storeId): array
    {
        if (!$ids) {
            return [];
        }
        try {
            $nameAttr = (int) $this->eavConfig->getAttribute(Category::ENTITY, 'name')->getId();
            $activeAttr = (int) $this->eavConfig->getAttribute(Category::ENTITY, 'is_active')->getId();
            if (!$nameAttr || !$activeAttr) {
                return [];
            }

            $connection = $this->resource->getConnection();
            $varchar = $this->resource->getTableName('catalog_category_entity_varchar');
            $int = $this->resource->getTableName('catalog_category_entity_int');
            $select = $connection->select()
                ->from(['e' => $this->resource->getTableName('catalog_category_entity')], ['entity_id'])
                ->joinLeft(['nd' => $varchar], "nd.entity_id = e.entity_id AND nd.attribute_id = {$nameAttr} AND nd.store_id = 0", [])
                ->joinLeft(['ns' => $varchar], "ns.entity_id = e.entity_id AND ns.attribute_id = {$nameAttr} AND ns.store_id = {$storeId}", [])
                ->joinLeft(['ad' => $int], "ad.entity_id = e.entity_id AND ad.attribute_id = {$activeAttr} AND ad.store_id = 0", [])
                ->joinLeft(['as_' => $int], "as_.entity_id = e.entity_id AND as_.attribute_id = {$activeAttr} AND as_.store_id = {$storeId}", [])
                ->columns(['name' => new \Zend_Db_Expr('COALESCE(ns.value, nd.value)')])
                ->where('e.entity_id IN (?)', $ids)
                ->where('COALESCE(as_.value, ad.value) = 1')
                ->order('e.position ASC')
                ->order('e.entity_id ASC');

            $out = [];
            foreach ($connection->fetchPairs($select) as $id => $name) {
                if (trim((string) $name) !== '') {
                    $out[(int) $id] = (string) $name;
                }
            }

            return $out;
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: category names unavailable: ' . $e->getMessage());

            return [];
        }
    }
}
