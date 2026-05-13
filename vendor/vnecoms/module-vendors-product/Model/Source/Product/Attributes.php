<?php

namespace Vnecoms\VendorsProduct\Model\Source\Product;

class Attributes extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $attrCollection;
    
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
     * @var \Vnecoms\VnedorsProduct\Helper\Data
     */
    protected $productHelper;

    /**
     * Attributes constructor.
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $collection
     * @param \Vnecoms\VendorsProduct\Helper\Data $productHelper
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $collection,
        \Vnecoms\VendorsProduct\Helper\Data $productHelper
    ) {
        $this->attrCollection = $collection;
        $this->attrCollection->addVisibleFilter();
        $this->productHelper = $productHelper;
    }
    
    /**
     * Retrieve all options array
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            foreach ($this->attrCollection as $attr) {
                if (in_array( $attr->getAttributeCode(), $this->productHelper->getIgnoreUpdateApprovalProductAttributes()))
                    continue;
                $this->_options[] = ['label' => $attr->getFrontendLabel(), 'value' => $attr->getAttributeCode()];
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
