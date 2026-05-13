<?php
namespace Vnecoms\VendorsProduct\Model\Plugin\CatalogWidget\Condition;

class Product
{
    /**
     * Vendor Product helper
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $productHelper;

    /**
     * @param \Vnecoms\VendorsProduct\Helper\Data $helper
     */
    public function __construct(
        \Vnecoms\VendorsProduct\Helper\Data $productHelper
    ) {
        $this->productHelper = $productHelper;
        return $this;
    }

    /**
     * @param \Magento\CatalogWidget\Model\Rule\Condition\Product $subject
     * @param $result
     * @return \Magento\CatalogWidget\Model\Rule\Condition\Product
     */
    public function afterLoadAttributeOptions(
        \Magento\CatalogWidget\Model\Rule\Condition\Product $subject,
        $result
    ) {
        $oldData = $subject->getAttributeOption();
        $attributes = $this->productHelper->getAdditionElasticAttribute();
        $result = array_merge($oldData, $attributes);
        $subject->setAttributeOption($result);
        return $subject;
    }
}
