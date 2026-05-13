<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Plugin;

class Layer
{
    /**
     * Vendor Product helper
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $vendorHelper;
    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    
    /**
     * @param \Vnecoms\VendorsProduct\Helper\Data $helper
     */
    public function __construct(
        \Vnecoms\Vendors\Helper\Data $helper,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->vendorHelper = $helper;
        $this->_coreRegistry = $coreRegistry;
        return $this;
    }
    
    /**
     * Before prepare product collection handler
     *
     * @param \Magento\Catalog\Model\Layer $subject
     * @param \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforePrepareProductCollection(
        \Magento\Catalog\Model\Layer $subject,
        \Magento\Catalog\Model\ResourceModel\Collection\AbstractCollection $collection
    ) {
        if(!$this->vendorHelper->moduleEnabled()) return;
        if($this->_coreRegistry->registry('vendor')) return;
        
        if($collection->isEnabledFlat()){
            $collection->getSelect()->where('select_from_product_id is null or select_from_product_id = (?)',0);
        }else{
            $collection->addAttributeToSelect('select_from_product_id');
            $condition = [
                ['attribute'=>'select_from_product_id', 'is' => new \Zend_Db_Expr('NULL')],
                ['attribute'=>'select_from_product_id', 'eq' => 0],
            ];
            $collection->addAttributeToFilter($condition, null,'left');
        }
    }

}
