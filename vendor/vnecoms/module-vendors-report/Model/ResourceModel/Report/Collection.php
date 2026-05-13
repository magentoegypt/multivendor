<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */


namespace Vnecoms\VendorsReport\Model\ResourceModel\Report;

class Collection extends \Magento\Reports\Model\ResourceModel\Report\Collection
{
    /**
     * Vendor Id
     *
     * @var int
     */
    protected $_vendorId;
    
    /**
     * Set Vendor Id
     *
     * @param int $vendorId
     * @return \Vnecoms\VendorsReport\Model\ResourceModel\Report\Collection
     */
    public function setVendorId($vendorId)
    {
        $this->_vendorId = $vendorId;
        return $this;
    }
    
    /**
     * Get Vendor Id
     *
     * @return number
     */
    public function getVendorId()
    {
        return $this->_vendorId;
    }
    
    /**
     * Get report for some interval
     *
     * @param int $fromDate
     * @param int $toDate
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    protected function _getReport($fromDate, $toDate)
    {
        if ($this->_reportCollection === null) {
            return [];
        }
        $reportResource = $this->_collectionFactory->create($this->_reportCollection);
        $reportResource->setDateRange($fromDate, $toDate)->setVendorId($this->getVendorId());
        return $reportResource;
    }
}
