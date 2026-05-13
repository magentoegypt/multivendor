<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsReport\Model\Report;

use Vnecoms\VendorsReport\Model\Source\Period;

/**
 * Adminhtml graph model
 *
 */
class Sales extends \Magento\Framework\DataObject
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
    
    /**
     * @var \Vnecoms\VendorsReport\Model\ResourceModel\Report\Sales
     */
    protected $_salesReportFactory;
    
    public function __construct(
        \Vnecoms\VendorsReport\Model\ResourceModel\Report\SalesFactory $salesReportFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        array $data = []
    ) {
        parent::__construct($data);
        $this->_salesReportFactory = $salesReportFactory;
        $this->_date = $date;
    }

    /**
     * @param $from
     * @param $to
     * @param $period
     * @param array $data
     * @return array
     */
    protected function _processOrderData($from, $to, $period, $data = [])
    {
        $newData = [];
        foreach ($data as $trans) {
            $newData[$trans['time']] = $trans;
        }

        $dateFormat = false;
        switch ($period) {
            case Period::PERIOD_DAY:
                $dateFormat = 'Y-m-d';
                $dateIncrement = '+1 day';
                $dateIncrementFormat = 'Y-m-d';
                break;
            case Period::PERIOD_WEEK:
                break;
            case Period::PERIOD_MONTH:
                $dateFormat = 'Y-m';
                $dateIncrement = '+1 month';
                $dateIncrementFormat = 'Y-m-1';
                break;
            case Period::PERIOD_QUARTER:
                break;
            case Period::PERIOD_YEAR:
                $dateFormat = 'Y';
                $dateIncrementFormat = 'Y-1-1';
                $dateIncrement = '+1 year';
                break;
        }
        
        if (!$dateFormat) {
            return array_values($newData);
        }
        
        $result = [];
        $datePointer = date($dateFormat, $this->_date->timestamp($from));
        $to = $this->_date->timestamp($to);
        while ($this->_date->timestamp($from) <= $to) {
            if (!isset($newData[$datePointer])) {
                $result[$datePointer] = [
                    'time' => $datePointer,
                    'report_grand_total' => 0,
                    'report_total_paid' => 0,
                    'report_grand_total' => 0,
                    'report_subtotal' => 0,
                    'report_order_count' => 0,
                    'report_shipping' => 0,
                    'report_discount' => 0,
                    'report_refunded' => 0,
                    'report_ordered_qty' => 0,
                ];
            } else {
                $result[$datePointer] = $newData[$datePointer];
            }
        
            $from = date($dateIncrementFormat, $this->_date->timestamp($from.$dateIncrement));
            $datePointer = date($dateFormat, $this->_date->timestamp($from));
        }
        
        return array_values($result);
        ;
    }

    /**
     * @param $from
     * @param $to
     * @param $period
     * @param $vendorId
     * @return array
     */
    public function getOrderTotalsDataForGraph($from, $to, $period, $vendorId)
    {
        $collection = $this->_salesReportFactory->create();
        $collection->filterOrder($from, $to, $period, $vendorId);

        $data = $this->_processOrderData($from, $to, $period, $collection->getData());
        return $data;
    }
    
    
    /**
     * Get Order Total By Month
     *
     * @param datetime $from
     * @param datetime $to
     * @param int $vendorId
     * @return multitype:
     */
    public function getOrderTotalsByMonth($from, $to, $vendorId)
    {
        $collection = $this->_salesReportFactory->create();
        $collection->groupOrdersByMonth($from, $to, $vendorId);
        
        $data = $collection->getData();
        $newData = [];
        foreach ($data as $trans) {
            $newData[$trans['time']] = $trans;
        }
        
        for ($i = 1; $i <= 12; $i ++) {
            if (!isset($newData[$i])) {
                $newData[$i] = [
                    'time' => $i,
                    'report_grand_total' => 0,
                    'report_total_paid' => 0,
                    'report_grand_total' => 0,
                    'report_subtotal' => 0,
                    'report_order_count' => 0,
                    'report_shipping' => 0,
                    'report_discount' => 0,
                    'report_refunded' => 0,
                    'report_ordered_qty' => 0,
                ];
            }
            $newData[$i]['time'] = __($this->_date->date('F', strtotime("2016-{$i}-01")));
            $newData[$i]['time_num'] = $i;
        }
        ksort($newData);
        
        return array_values($newData);
    }
    
    
    /**
     * Get Order Total By Day
     *
     * @param datetime $from
     * @param datetime $to
     * @param int $vendorId
     * @return multitype:
     */
    public function getOrderTotalsByDay($from, $to, $vendorId)
    {
        $collection = $this->_salesReportFactory->create();
        $collection->groupOrdersByDay($from, $to, $vendorId);
    
        $data = $collection->getData();
        $newData = [];
        foreach ($data as $trans) {
            $newData[$trans['time']] = $trans;
        }
        $dayOfWeekArr = [
            0 => __("Sunday"),
            1 => __("Monday"),
            2 => __("Tuesday"),
            3 => __("Wednesday"),
            4 => __("Thursday"),
            5 => __("Friday"),
            6 => __("Staturday"),
        ];
        for ($i = 0; $i < 7; $i ++) {
            if (!isset($newData[$i])) {
                $newData[$i] = [
                    'time' => $i,
                    'report_grand_total' => 0,
                    'report_total_paid' => 0,
                    'report_grand_total' => 0,
                    'report_subtotal' => 0,
                    'report_order_count' => 0,
                    'report_shipping' => 0,
                    'report_discount' => 0,
                    'report_refunded' => 0,
                    'report_ordered_qty' => 0,
                ];
            }
            $newData[$i]['time'] = $dayOfWeekArr[$i];
            $newData[$i]['time_num'] = $i;
        }
        ksort($newData);
    
        return array_values($newData);
    }
    
    /**
     * Get Order Total By Day
     *
     * @param datetime $from
     * @param datetime $to
     * @param int $vendorId
     * @return multitype:
     */
    public function getOrderTotalsByHour($from, $to, $vendorId)
    {
        $collection = $this->_salesReportFactory->create();
        $collection->groupOrdersByDay($from, $to, $vendorId);
    
        $data = $collection->getData();
        $newData = [];
        foreach ($data as $trans) {
            $newData[$trans['time']] = $trans;
        }
        $hourOfDay = [
            0 => __("0:00 - 0:59"),
            1 => __("Monday"),
            2 => __("Tuesday"),
            3 => __("Wednesday"),
            4 => __("Thursday"),
            5 => __("Friday"),
            6 => __("Staturday"),
        ];
        for ($i = 0; $i < 24; $i ++) {
            if (!isset($newData[$i])) {
                $newData[$i] = [
                    'time' => $i,
                    'report_grand_total' => 0,
                    'report_total_paid' => 0,
                    'report_grand_total' => 0,
                    'report_subtotal' => 0,
                    'report_order_count' => 0,
                    'report_shipping' => 0,
                    'report_discount' => 0,
                    'report_refunded' => 0,
                    'report_ordered_qty' => 0,
                ];
            }
            /* $newData[$i]['time'] = sprintf("%02d:00 - %02d:59",$i,$i); */
            $newData[$i]['time'] = sprintf("%02d:00", $i, $i);
            $newData[$i]['time_num'] = $i;
        }
        ksort($newData);
    
        return array_values($newData);
    }
    
    /**
     * Get product sold data
     *
     * @param datetime $from
     * @param datetime $to
     * @param string $period
     * @param int $vendorId
     * @return array
     */
    public function getProductSoldDataForGraph($from, $to, $period, $vendorId)
    {
        $collection = $this->_salesReportFactory->create();
        $collection->getSoldProductData($from, $to, $period, $vendorId);
    
        $data = $collection->getData();
        
        return array_values($data);
    }
}
