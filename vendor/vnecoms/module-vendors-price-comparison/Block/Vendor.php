<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsPriceComparison\Block;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Config;
use Vnecoms\Vendors\Model\VendorFactory;
use Vnecoms\VendorsConfig\Helper\Data as ConfigHelper;
use Magento\Framework\App\Filesystem\DirectoryList;
use Vnecoms\VendorsProduct\Helper\Data as ProductHelper;
use Vnecoms\Vendors\Helper\Data as VendorHelper;
use Vnecoms\VendorsPriceComparison\Helper\Data as PriceComparisonHelper;

class Vendor extends \Magento\Framework\View\Element\Template
{
    /**
     * @var array
     */
    protected $jsLayout;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var array|\Magento\Checkout\Block\Checkout\LayoutProcessorInterface[]
     */
    protected $layoutProcessors;

    /**
     * @var \Vnecoms\VendorsPriceComparison\Helper\Data
     */
    protected $_priceComparisonHelper;

    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $_localeFormat;

    /**
     * The list of loaded vendors
     * @var array
     */
    protected $_vendors = [];

    /**
     * [protected description]
     * @var \Vnecoms\VendorsPriceComparison\Model\LoadProduct
     */
    protected $_loadProduct;

    /**
     * [__construct description]
     * @param Context                                       $context               [description]
     * @param Registry                                      $coreRegistry          [description]
     * @param PriceComparisonHelper                         $priceComparisonHelper [description]
     * @param MagentoFrameworkLocaleFormatInterface         $localeFormat          [description]
     * @param VnecomsVendorsPriceComparisonModelLoadProduct $loadProduct           [description]
     * @param array                                         $layoutProcessors      [description]
     * @param array                                         $data                  [description]
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PriceComparisonHelper $priceComparisonHelper,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Vnecoms\VendorsPriceComparison\Model\LoadProduct $loadProduct,
        array $layoutProcessors = [],
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $coreRegistry;
        $this->_priceComparisonHelper = $priceComparisonHelper;
        $this->_localeFormat = $localeFormat;
        $this->jsLayout = isset($data['jsLayout']) && is_array($data['jsLayout']) ? $data['jsLayout'] : [];
        $this->layoutProcessors = $layoutProcessors;
        $this->_loadProduct = $loadProduct;
    }

    /**
     * @return string
     */
    public function getJsLayout()
    {
        $this->jsLayout['components']['vendor']['products'] = $this->getProducts();
        $this->jsLayout['components']['vendor']['page_size'] = $this->_priceComparisonHelper->getShowingNumber();
        $this->jsLayout['components']['vendor']['show_country_filter'] = $this->_priceComparisonHelper->showCountryFilter();
        $this->jsLayout['components']['vendor']['select_from_product_id'] = $this->getProduct()->getId();

        $this->jsLayout['components']['vendor']['children']['vendor_info']['config']['show_address'] = $this->_priceComparisonHelper->canShowAddress();
        $this->jsLayout['components']['vendor']['children']['vendor_info']['config']['show_sales_count'] = $this->_priceComparisonHelper->canShowSalesCount();
        $this->jsLayout['components']['vendor']['children']['vendor_info']['config']['show_joined_date'] = $this->_priceComparisonHelper->canShowJoinedDate();
        $this->jsLayout['components']['vendor']['children']['vendor_description']['config']['show_address'] = $this->_priceComparisonHelper->canShowAddress();
        $this->jsLayout['components']['vendor']['children']['vendor_description']['config']['show_sales_count'] = $this->_priceComparisonHelper->canShowSalesCount();
        $this->jsLayout['components']['vendor']['children']['vendor_description']['config']['show_joined_date'] = $this->_priceComparisonHelper->canShowJoinedDate();

        $this->jsLayout['components']['vendor']['children']['product_price']['priceFormat'] = $this->getPriceFormat();
        $this->jsLayout['components']['vendor']['children']['product_price']['basePriceFormat'] = $this->getBasePriceFormat();


        foreach ($this->layoutProcessors as $processor) {
            $this->jsLayout = $processor->process($this->jsLayout);
        }
        return \Laminas\Json\Json::encode($this->jsLayout);
    }

    /**
     * Get products
     *
     * @return multitype:unknown
     */
    public function getProducts(){
          $dataPost = ['parentProductId' => $this->getProduct()->getId()];
          return $this->_loadProduct->getProductComparison($dataPost);
    }


    /**
     * Get current product
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct(){
        return $this->_coreRegistry->registry('product');
    }

    /**
     * (non-PHPdoc)
     * @see \Magento\Framework\View\Element\AbstractBlock::toHtml()
     */
    public function toHtml(){
        if(!$this->getProduct() || !$this->getProducts()) return '';

        return parent::toHtml();
    }

      /**
     * Get price format json.
     *
     * @return string
     */
    public function getPriceFormat()
    {
        return $this->_localeFormat->getPriceFormat();
    }

    /**
     * Get price format json.
     *
     * @return string
     */
    public function getBasePriceFormat()
    {
        return $this->_localeFormat->getPriceFormat(null, $this->_storeManager->getStore()->getBaseCurrencyCode());
    }
}
