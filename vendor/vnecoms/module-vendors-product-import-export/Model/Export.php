<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\VendorsProductImportExport\Model;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Import model
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 *
 * @method string getBehavior() getBehavior()
 * @method \Magento\ImportExport\Model\Import setEntity() setEntity(string $value)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Export extends \Magento\ImportExport\Model\Export
{
    /**
     * @var \Vnecoms\Vendors\Model\Vendor
     */
    protected $_vendor;

    /**
     * Set Vendor
     * 
     * @param \Vnecoms\Vendors\Model\Vendor $vendor
     */
    public function setVendor(\Vnecoms\Vendors\Model\Vendor $vendor){
        $this->_vendor = $vendor;
        return $this;
    }
    
    /**
     * Get Vendor
     * 
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor(){
        return $this->_vendor;
    }
    
    /**
     * Override standard entity getter.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @return string
     */
    public function getEntity()
    {
        return 'catalog_product';
    }
    
    /**
     * (non-PHPdoc)
     * @see \Magento\ImportExport\Model\Import::_getEntityAdapter()
     */
    protected function _getEntityAdapter()
    {
        $adapter = parent::_getEntityAdapter();
        if(!$adapter->getVendor()){
            $adapter->setVendor($this->getVendor());
        }
    
        return $adapter;
    }
}
