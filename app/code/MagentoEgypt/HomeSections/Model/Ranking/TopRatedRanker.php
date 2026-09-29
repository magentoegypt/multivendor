<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Model\Ranking;

use Magento\Framework\App\ResourceConnection;

/**
 * Highest-rated products, from Block\PickedForYou::topRated(): at least two
 * approved reviews, best average rating first, then most reviews.
 *
 * rating_summary is a PERCENTAGE. Two changes from the block, both the fix
 * VendorMeta documents: only the default-scope summary row counts
 * (review_entity_summary has one row per store, and the vendor-panel store's
 * rows are all 0, which dragged averages down unevenly), and only PRODUCT
 * reviews (entity type 1) are joined.
 *
 * Ranking only: the caller applies the storefront visibility gate.
 */
class TopRatedRanker
{
    /** review_entity / review_entity_summary.entity_type for products. */
    private const PRODUCT_ENTITY = 1;

    /** Minimum approved reviews for a product to rank. */
    public const MIN_REVIEWS = 2;

    public function __construct(private readonly ResourceConnection $resource)
    {
    }

    /**
     * @return int[]
     */
    public function rank(int $limit, int $offset = 0): array
    {
        if ($limit < 1) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(['s' => $this->resource->getTableName('review_entity_summary')], ['product_id' => 's.entity_pk_value'])
            ->join(['r' => $this->resource->getTableName('review')], 'r.entity_pk_value = s.entity_pk_value', [])
            ->where('s.store_id = ?', 0)
            ->where('s.entity_type = ?', self::PRODUCT_ENTITY)
            ->where('s.rating_summary IS NOT NULL')
            ->where('r.entity_id = ?', self::PRODUCT_ENTITY)
            ->where('r.status_id = ?', 1)
            ->group('s.entity_pk_value')
            ->having('COUNT(DISTINCT r.review_id) >= ?', self::MIN_REVIEWS)
            ->order('AVG(s.rating_summary) DESC')
            ->order('COUNT(DISTINCT r.review_id) DESC')
            ->order('s.entity_pk_value DESC')
            ->limit($limit, max(0, $offset));

        return array_map('intval', array_column($connection->fetchAll($select), 'product_id'));
    }
}
