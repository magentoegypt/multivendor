<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Model\Ranking;

use Magento\Framework\App\ResourceConnection;

/**
 * Best sellers by units actually ordered, from Block\BestSellers::
 * getRankedProductIds().
 *
 * Reads sales_order_item, not sales_bestsellers_aggregated_*: that aggregate is
 * refreshed by a report cron that does not run here (see the block). Only
 * top-level lines count (parent_item_id IS NULL), so a configurable or bundle
 * sale is not counted twice.
 *
 * One change from the block: lines of CANCELLED orders are excluded. A
 * cancelled order sold nothing, and the attack orders of September (56
 * cancelled on 2026-09-29) would otherwise rank their products. Recommended
 * for the website block too.
 *
 * Ranking only: the caller applies the storefront visibility gate.
 */
class BestSellerRanker
{
    public function __construct(private readonly ResourceConnection $resource)
    {
    }

    /**
     * Product ids, most units first.
     *
     * @return int[]
     */
    public function rank(int $limit, int $offset = 0): array
    {
        if ($limit < 1) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(['oi' => $this->resource->getTableName('sales_order_item')], ['product_id'])
            ->join(['o' => $this->resource->getTableName('sales_order')], 'o.entity_id = oi.order_id', [])
            ->columns(['qty' => 'SUM(oi.qty_ordered)'])
            ->where('oi.parent_item_id IS NULL')
            ->where('oi.product_id IS NOT NULL')
            ->where('o.state <> ?', 'canceled')
            ->group('oi.product_id')
            ->order('qty DESC')
            ->order('oi.product_id DESC')
            ->limit($limit, max(0, $offset));

        return array_map('intval', array_column($connection->fetchAll($select), 'product_id'));
    }
}
