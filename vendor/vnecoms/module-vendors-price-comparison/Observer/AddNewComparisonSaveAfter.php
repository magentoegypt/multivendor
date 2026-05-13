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
use Vnecoms\VendorsPriceComparison\Model\Source\Product\Main;
use Magento\Store\Model\Store;

class AddNewComparisonSaveAfter implements ObserverInterface
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
     * AddNewComparisonSaveAfter constructor.
     * @param ProductHelper $productHelper
     * @param PriceComparisonHelper $priceComparisonHelper
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     */
    public function __construct(
        ProductHelper $productHelper,
        PriceComparisonHelper $priceComparisonHelper,
        CollectionFactory $collectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        $this->_priceComparisonHelper   = $priceComparisonHelper;
        $this->_productHelper           = $productHelper;
        $this->_collectionFactory       = $collectionFactory;
        $this->productFactory           = $productFactory;
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
        $mainProductId = $observer->getMainProductId();
        $this->copyProductInfo($product, $mainProductId);
    }

    /**
     * @param \Magento\Catalog\Model\Product $subProduct
     * @param $mainProductId
     * @return $this|void
     */
    protected function copyProductInfo(\Magento\Catalog\Model\Product $subProduct, $mainProductId){
        foreach ($subProduct->getStoreIds() as $storeId) {
            $main = $this->productFactory->create()->setStoreId($storeId)->load($mainProductId);
            $sub = $this->productFactory->create()->setStoreId($storeId)->load($subProduct->getId());
            if(!$main->getId() || !$sub->getId()) continue;
            $this->copyProductData($sub, $main);
            try {
                $sub->save();
            } catch (\Exception $e) {
                //to do something
            }
        }
        return $this;
    }

    /*
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
     * @param \Magento\Catalog\Model\Product $desProduct
     * @param \Magento\Catalog\Model\Product $sourceProduct
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
