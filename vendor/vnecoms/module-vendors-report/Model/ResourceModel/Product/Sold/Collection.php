<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsReport\Model\ResourceModel\Product\Sold;

/**
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Collection extends \Magento\Reports\Model\ResourceModel\Order\Collection
{
    /**
     * Filter by vendor id
     *
     * @param unknown $vendorId
     */
    public function setVendorId($vendorId)
    {
        $connection = $this->getConnection();
        $orderTableAliasName = $connection->quoteIdentifier('order');
        $this->getSelect()->where("{$orderTableAliasName}.vendor_id = ?", $vendorId);
    }
    
    /**
     * Set Date range to collection
     *
     * @param int $from
     * @param int $to
     * @return $this
     */
    public function setDateRange($from, $to)
    {
        $this->_reset()->addOrderedQty(
            $from,
            $to
        )->setOrder(
            'ordered_qty',
            self::SORT_ORDER_DESC
        );
        return $this;
    }

    /**
     * Add ordered qty's
     *
     * @param string $from
     * @param string $to
     * @return $this
     */
    public function addOrderedQty($from = '', $to = '')
    {
        $connection = $this->getConnection();
        $orderTableAliasName = $connection->quoteIdentifier('order');

        $orderJoinCondition = [
            $orderTableAliasName . '.entity_id = order_items.vendor_order_id',
            $connection->quoteInto("{$orderTableAliasName}.status <> ?", \Magento\Sales\Model\Order::STATE_CANCELED),
        ];

        if ($from != '' && $to != '') {
            $fieldName = $orderTableAliasName . '.created_at';
            $orderJoinCondition[] = $this->prepareBetweenSql($fieldName, $from, $to);
        }

        $this->getSelect()->reset()->from(
            ['order_items' => $this->getTable('sales_order_item')],
            ['ordered_qty' => 'SUM(order_items.qty_ordered)', 'order_items_name' => 'order_items.name']
        )->joinInner(
            ['order' => $this->getTable('ves_vendor_sales_order')],
            implode(' AND ', $orderJoinCondition),
            []
        )->where(
            'parent_item_id IS NULL'
        )->group(
            'order_items.product_id'
        )->having(
            'SUM(order_items.qty_ordered) > ?',
            0
        );
        return $this;
    }

    /**
     * Set store filter to collection
     *
     * @param array $storeIds
     * @return $this
     */
    public function setStoreIds($storeIds)
    {
        return $this;
    }

    /**
     * Set order
     *
     * @param string $attribute
     * @param string $dir
     * @return $this
     */
    public function setOrder($attribute, $dir = self::SORT_ORDER_DESC)
    {
        if (in_array($attribute, ['orders', 'ordered_qty'])) {
            $this->getSelect()->order($attribute . ' ' . $dir);
        } else {
            parent::setOrder($attribute, $dir);
        }

        return $this;
    }

    /**
     * Prepare between sql
     *
     * @param string $fieldName Field name with table suffix ('created_at' or 'main_table.created_at')
     * @param string $from
     * @param string $to
     * @return string Formatted sql string
     */
    protected function prepareBetweenSql($fieldName, $from, $to)
    {
        return sprintf(
            '(%s BETWEEN %s AND %s)',
            $fieldName,
            $this->getConnection()->quote($from),
            $this->getConnection()->quote($to)
        );
    }
}
