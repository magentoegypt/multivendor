<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsReport\Model\ResourceModel\Report;

use Vnecoms\VendorsReport\Model\Source\Period;

/**
 * Adminhtml graph model
 *
 */
class Sales extends \Vnecoms\VendorsSales\Model\ResourceModel\Order\Collection
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
    
    /**
     * @var \Vnecoms\VendorsReport\Helper\Data
     */
    protected $_reportHelper;
    
    /**
     * Constructor
     *
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Vnecoms\VendorsReport\Helper\Data $reportHelper
    ) {
        $this->_date = $date;
        $this->_reportHelper = $reportHelper;
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager
        );
    }
    
    /**
     * Filter order
     *
     * @param unknown $from
     * @param unknown $to
     * @param unknown $period
     * @param unknown $vendorId
     */
    public function filterOrder($from, $to, $period, $vendorId)
    {
        $columns = [
            'report_total_paid' => 'SUM( base_total_paid )',
            'report_grand_total' => 'SUM(base_grand_total)',
            'report_subtotal' => 'SUM(base_subtotal)',
            'report_order_count' => 'COUNT(entity_id)',
            'report_shipping' => 'SUM(base_shipping_amount)',
            'report_discount' => 'SUM(base_discount_amount)',
            'report_refunded' => 'SUM(base_total_refunded)',
            'report_ordered_qty' => 'SUM(total_qty_ordered)',
        ];
        $filterField = $this->_reportHelper->getDateFilterField();
        
        switch ($period) {
            case Period::PERIOD_DAY:
                $columns['time'] = "DATE_FORMAT({$filterField},'%Y-%m-%d')";
                break;
            case Period::PERIOD_WEEK:
                break;
            case Period::PERIOD_MONTH:
                $columns['time'] = "DATE_FORMAT({$filterField},'%Y-%m')";
                break;
            case Period::PERIOD_QUARTER:
                $columns['time'] = "CONCAT(YEAR({$filterField}),'/','Q',QUARTER(created_at))";
                break;
            case Period::PERIOD_YEAR:
                $columns['time'] = "DATE_FORMAT({$filterField},'%Y')";
                break;
        }
        $this->getSelect()->reset('columns')->columns(
            $columns
        )->where(
            "{$filterField} > :from_date"
        )->where(
            "{$filterField} < :to_date"
        )->where(
            'vendor_id = :vendor_id'
        )->group(
            'time'
        )->order('time ASC');
        
        $from = date('Y-m-d', $this->_date->timestamp($from));
        $to = date('Y-m-d', $this->_date->timestamp($to));
        $to = date('Y-m-d H:s:i', $this->_date->timestamp($to.'+23 hours +59minutes + 59seconds'));

        $bind = [
            'from_date' => $from,
            'to_date' => $to,
            'vendor_id' => $vendorId
        ];
        foreach ($bind as $key => $value) {
            $this->addBindParam($key, $value);
        }
    }
    
    /**
     * Group orders by time column
     * @param unknown $from
     * @param unknown $to
     * @param unknown $vendorId
     * @param unknown $timeColumnFormat
     */
    public function groupOrders($from, $to, $vendorId, $timeColumnFormat)
    {
        $filterField = $this->_reportHelper->getDateFilterField();
        $columns = [
            'time' => "DATE_FORMAT({$filterField},'{$timeColumnFormat}')",
            'report_total_paid' => 'SUM( base_total_paid )',
            'report_grand_total' => 'SUM(base_grand_total)',
            'report_subtotal' => 'SUM(base_subtotal)',
            'report_order_count' => 'COUNT(entity_id)',
            'report_shipping' => 'SUM(base_shipping_amount)',
            'report_discount' => 'SUM(base_discount_amount)',
            'report_refunded' => 'SUM(base_total_refunded)',
            'report_ordered_qty' => 'SUM(total_qty_ordered)',
            ];
        
        $this->getSelect()->reset('columns')->columns(
            $columns
        )->where(
            "{$filterField} > :from_date"
        )->where(
            "{$filterField} < :to_date"
        )->where(
            'vendor_id = :vendor_id'
        )->group(
            'time'
        )->order('time ASC');
        
        $from = date('Y-m-d', $this->_date->timestamp($from));
        $to = date('Y-m-d', $this->_date->timestamp($to));
        $to = date('Y-m-d H:s:i', $this->_date->timestamp($to.'+23 hours +59minutes + 59seconds'));
        
            $bind = [
                'from_date' => $from,
            'to_date' => $to,
        'vendor_id' => $vendorId
            ];
        
            foreach ($bind as $key => $value) {
                $this->addBindParam($key, $value);
            }
    }
    
    /**
     * Filter order
     *
     * @param string $from
     * @param string $to
     * @param string $period
     * @param int $vendorId
     */
    public function groupOrdersByMonth($from, $to, $vendorId)
    {
        $this->groupOrders($from, $to, $vendorId, '%c');
    }
    
    /**
     * Filter order
     *
     * @param string $from
     * @param string $to
     * @param string $period
     * @param int $vendorId
     */
    public function groupOrdersByDay($from, $to, $vendorId)
    {
        $this->groupOrders($from, $to, $vendorId, '%w');
    }
    
    /**
     * Filter order
     *
     * @param string $from
     * @param string $to
     * @param string $period
     * @param int $vendorId
     */
    public function groupOrdersByHour($from, $to, $vendorId)
    {
        $this->groupOrders($from, $to, $vendorId, '%k');
    }
    
    public function getSoldProductData($from, $to, $period, $vendorId)
    {
        
        
        $connection = $this->getConnection();
        $orderTableAliasName = $connection->quoteIdentifier('order');
        
        $orderJoinCondition = [
            $orderTableAliasName . '.entity_id = order_items.vendor_order_id',
            $connection->quoteInto("{$orderTableAliasName}.status <> ?", \Magento\Sales\Model\Order::STATE_CANCELED),
        ];
        
        $columns = [
            'ordered_qty' => 'SUM(order_items.qty_ordered)',
            'order_items_name' => 'order_items.name'
        ];
        
        $filterField = $this->_reportHelper->getDateFilterField();
        
        switch ($period) {
            case Period::PERIOD_DAY:
                $columns['time'] = "DATE_FORMAT({$orderTableAliasName}.{$filterField},'%Y-%c-%d')";
                break;
            case Period::PERIOD_WEEK:
                break;
            case Period::PERIOD_MONTH:
                $columns['time'] = "DATE_FORMAT({$orderTableAliasName}.{$filterField},'%Y-%c')";
                break;
            case Period::PERIOD_QUARTER:
                $columns['time'] = "CONCAT(YEAR({$orderTableAliasName}.{$filterField}),'/','Q',QUARTER({$orderTableAliasName}.created_at))";
                break;
            case Period::PERIOD_YEAR:
                $columns['time'] = "DATE_FORMAT({$orderTableAliasName}.{$filterField},'%Y')";
                break;
        }
        
        $this->getSelect()->reset()->from(
            ['order_items' => $this->getTable('sales_order_item')],
            $columns
        )->joinInner(
            ['order' => $this->getTable('ves_vendor_sales_order')],
            implode(' AND ', $orderJoinCondition),
            []
        )->where(
            'parent_item_id IS NULL'
        )->group(
            'time'
        )->group(
            'order_items.product_id'
        )->having(
            'SUM(order_items.qty_ordered) > ?',
            0
        )->order('time ASC');
    }
}
