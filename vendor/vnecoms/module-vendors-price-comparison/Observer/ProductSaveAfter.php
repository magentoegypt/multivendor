<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsProduct\Helper\Data as ProductHelper;
use Vnecoms\VendorsPriceComparison\Helper\Data as PriceComparisonHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class ProductSaveAfter implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsPriceComparison\Helper\Data
     */
    protected $_priceComparisonHelper;

    /**
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    protected $_productHelper;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var array
     */
    protected $_copyAttributes;

    /**
     * @var boolean
     */
    protected $copyFlag = true;

    /**
     * Vendor helper
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $vendorHelper;

    /**
     * @var \Vnecoms\VendorsPriceComparison\Model\Process
     */
    protected $process;

    /**
     * @var \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable
     */
    protected $_catalogProductTypeConfigurable;

    /**
     * ProductSaveAfter constructor.
     * @param ProductHelper $productHelper
     * @param PriceComparisonHelper $priceComparisonHelper
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Vnecoms\Vendors\Helper\Data $helper
     * @param \Vnecoms\VendorsPriceComparison\Model\Process $process
     * @param \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable
     */
    public function __construct(
        ProductHelper $productHelper,
        PriceComparisonHelper $priceComparisonHelper,
        CollectionFactory $collectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Vnecoms\Vendors\Helper\Data $helper,
        \Vnecoms\VendorsPriceComparison\Model\Process $process,
        \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable $catalogProductTypeConfigurable
    ) {
        $this->_priceComparisonHelper   = $priceComparisonHelper;
        $this->_productHelper           = $productHelper;
        $this->_collectionFactory       = $collectionFactory;
        $this->productFactory           = $productFactory;
        $this->process = $process;
        $this->_catalogProductTypeConfigurable = $catalogProductTypeConfigurable;
    }

    /**
     * Save product data for all child products
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product*/
        $product = $observer->getProduct();
        $this->copyProductInfo($product);
        $this->processMainProduct($product);
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     * @return $this|void
     */
    protected function copyProductInfo(\Magento\Catalog\Model\Product $product){
        /* Just return if it's a selected product and sell*/
        if($product->getData('select_from_product_id') || !$this->copyFlag) return;

        $collection = $this->_collectionFactory->create();
        $collection->addAttributeToFilter('select_from_product_id',$product->getId());
        /* Return if there is no product select and sell from current product*/
        if($collection->count()) return;

        foreach($collection as $subProduct){
            $subProduct->load($subProduct->getId());
            $this->copyProductData($subProduct, $product);
            $subProduct->save();
        }
        return $this;
    }

    /**
     * Process main product
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    protected function processMainProduct(\Magento\Catalog\Model\Product $product){
        $mainProductId = $product->getData('select_from_product_id')?
            $product->getData('select_from_product_id'):$product->getId();
        $this->process->saveQueueByProductIdType($mainProductId, "update");

        //process parent product
        $parentByChilds = $this->_catalogProductTypeConfigurable->getParentIdsByChild($product->getId());
        if ($parentByChilds) {
            $collectionParents = $this->_collectionFactory->create();
            $collectionParents->addAttributeToSelect("*");
            $collectionParents->addAttributeToFilter('entity_id', ["IN" => $parentByChilds]);

            foreach ($collectionParents as $product) {
                $mainProductId = $product->getData('select_from_product_id')?
                    $product->getData('select_from_product_id'):$product->getId();
                $this->process->saveQueueByProductIdType($mainProductId, "update");
            }
        }
    }

    /**
     *
     * @return multitype:
     */
    public function getCopyAttributes(){
        if($this->_copyAttributes === null){
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $productAttrCollection = $om->create('Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection')
                ->addVisibleFilter();

            $notUsedProductAttr = $this->_priceComparisonHelper->getAllowedAttribute();
            $notUsedProductAttr = array_merge(
                $notUsedProductAttr,
                array_values($this->_productHelper->getNotUsedVendorAttributes())
            );
            $attributes = [];

            foreach($productAttrCollection as $attribute){
                $attrCode = $attribute->getAttributeCode();
                if(!in_array($attrCode, $notUsedProductAttr)) $attributes[] = $attrCode;
            }
            $this->_copyAttributes = $attributes;
        }

        return $this->_copyAttributes;
    }

    /**
     * Copy product data
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    private function copyProductData(
        \Magento\Catalog\Model\Product $desProduct,
        \Magento\Catalog\Model\Product $sourceProduct
    ) {
        foreach($this->getCopyAttributes() as $attributeCode){
            $desProduct->setData($attributeCode, $sourceProduct->getData($attributeCode));
        }
    }
}
