<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Model\Ranking;

use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Products of a category (children included), in a sort — the category rails
 * and "Popular Products" of the Home.
 *
 * Reads the category-product index of the store view
 * (catalog_category_product_index_store<N>), which is anchor-aware exactly
 * like the category page, and defaults to NEWEST (entity id descending):
 * the default of the website's CatalogWidget ProductsList that renders those
 * rails today. No category means the store's root category, i.e. the whole
 * visible catalogue.
 *
 * Sorts: NEWEST, POSITION (category position), PRICE_ASC / PRICE_DESC (indexed
 * final price, guest group), TOP_RATED and BEST_SELLING (the global rankings,
 * restricted to the category).
 *
 * Ranking only: the caller applies the storefront visibility gate.
 */
class CatalogRanker
{
    public const NEWEST = 'NEWEST';
    public const POSITION = 'POSITION';
    public const PRICE_ASC = 'PRICE_ASC';
    public const PRICE_DESC = 'PRICE_DESC';
    public const TOP_RATED = 'TOP_RATED';
    public const BEST_SELLING = 'BEST_SELLING';

    /** How deep the global rankings are read before being cut to a category. */
    private const GLOBAL_DEPTH = 1000;

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly TableMaintainer $tableMaintainer,
        private readonly StoreManagerInterface $storeManager,
        private readonly TopRatedRanker $topRated,
        private readonly BestSellerRanker $bestSellers
    ) {
    }

    /**
     * @return int[]
     */
    public function rank(int $storeId, ?int $categoryId, ?string $sort, int $limit, int $offset = 0): array
    {
        if ($limit < 1) {
            return [];
        }

        $store = $this->storeManager->getStore($storeId);
        $categoryId = $categoryId ?: (int) $store->getRootCategoryId();
        $sort = strtoupper((string) $sort);

        if ($sort === self::TOP_RATED || $sort === self::BEST_SELLING) {
            $ranked = $sort === self::TOP_RATED
                ? $this->topRated->rank(self::GLOBAL_DEPTH)
                : $this->bestSellers->rank(self::GLOBAL_DEPTH);

            return array_slice($this->inCategory($ranked, $storeId, $categoryId), max(0, $offset), $limit);
        }

        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(['idx' => $this->tableMaintainer->getMainTable($storeId)], ['product_id'])
            ->where('idx.category_id = ?', $categoryId)
            ->where('idx.store_id = ?', $storeId)
            ->where('idx.visibility IN (?)', [Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH])
            ->group('idx.product_id')
            ->limit($limit, max(0, $offset));

        switch ($sort) {
            case self::POSITION:
                $select->order('MIN(idx.position) ASC')->order('idx.product_id DESC');
                break;
            case self::PRICE_ASC:
            case self::PRICE_DESC:
                $select->join(
                    ['price' => $this->resource->getTableName('catalog_product_index_price')],
                    'price.entity_id = idx.product_id AND price.customer_group_id = 0 AND price.website_id = '
                    . (int) $store->getWebsiteId(),
                    []
                )
                    ->order('MIN(price.min_price) ' . ($sort === self::PRICE_ASC ? 'ASC' : 'DESC'))
                    ->order('idx.product_id DESC');
                break;
            default:
                $select->order('idx.product_id DESC');
        }

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * The ids of $ranked that are in the category (children included), order kept.
     *
     * @param int[] $ranked
     * @return int[]
     */
    private function inCategory(array $ranked, int $storeId, int $categoryId): array
    {
        if (!$ranked) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $members = array_flip(array_map('intval', $connection->fetchCol(
            $connection->select()
                ->from($this->tableMaintainer->getMainTable($storeId), ['product_id'])
                ->where('category_id = ?', $categoryId)
                ->where('store_id = ?', $storeId)
                ->where('product_id IN (?)', $ranked)
        )));

        return array_values(array_filter($ranked, static fn (int $id): bool => isset($members[$id])));
    }
}
