<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Model\Source\Product;

class Attribute extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Grid\Collection
     */
    protected $_attributeCollection;
    
    /**
     * Options array
     *
     * @var array
     */
    protected $_options = null;
    
    /**
     * @var int
     */
    protected $_entityTypeId = null;
    
    /**
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Grid\Collection $setCollection
     */
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $collection
    ) {
        $this->_entityTypeId = $productFactory->create()->getResource()->getTypeId();
        $this->_attributeCollection = $collection;
        $this->_attributeCollection->setEntityTypeFilter($this->_entityTypeId);
        $this->_attributeCollection->addFieldToFilter('attribute_code',['neq' => 'name']);
    }
    
    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            foreach ($this->_attributeCollection as $attribute) {
                $this->_options[] = ['label' => $attribute->getFrontendLabel(), 'value' => $attribute->getAttributeCode()];
            }
            
        }
        return $this->_options;
    }

    /**
     * Retrieve option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $_options = [];
        foreach ($this->getAllOptions() as $option) {
            $_options[$option['value']] = $option['label'];
        }
        return $_options;
    }
    
    
    /**
     * Get options as array
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }
    
}
