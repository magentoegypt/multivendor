<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsSearch\Block;

use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Layer\Resolver as LayerResolver;
use Vnecoms\VendorsSearch\Helper\Data;
use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Search\Model\QueryFactory;

/**
 * Product search result block.
 */
class Result extends Template
{
    /**
     * Catalog Product collection.
     *
     * @var Collection
     */
    protected $productCollection;

    /**
     * Catalog search data.
     *
     * @var Data
     */
    protected $catalogSearchData;

    /**
     * Catalog layer.
     *
     * @var \Magento\Catalog\Model\Layer
     */
    protected $catalogLayer;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var \Vnecoms\VendorsConfig\Helper\Data
     */
    protected $vendorConfig;

    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $vendorPageHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * Result constructor.
     *
     * @param Context                            $context
     * @param LayerResolver                      $layerResolver
     * @param Data                               $catalogSearchData
     * @param QueryFactory                       $queryFactory
     * @param \Vnecoms\VendorsPage\Helper\Data   $vendorPageHelper
     * @param \Magento\Framework\Registry        $registry
     * @param \Vnecoms\VendorsConfig\Helper\Data $vendorConfig
     * @param array                              $data
     */
    public function __construct(
        Context $context,
        LayerResolver $layerResolver,
        Data $catalogSearchData,
        QueryFactory $queryFactory,
        \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsConfig\Helper\Data $vendorConfig,
        array $data = []
    ) {
        $this->catalogLayer = $layerResolver->get();
        $this->catalogSearchData = $catalogSearchData;
        $this->queryFactory = $queryFactory;
        $this->vendorConfig = $vendorConfig;
        $this->_coreRegistry = $registry;
        $this->vendorPageHelper = $vendorPageHelper;

        parent::__construct($context, $data);
    }

    /**
     * Retrieve query model object.
     *
     * @return \Magento\Search\Model\Query
     */
    protected function _getQuery()
    {
        return $this->queryFactory->get();
    }

    /**
     * Prepare layout.
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $title = $this->getSearchQueryText();
        $this->pageConfig->getTitle()->set($title);
        // add Home breadcrumb
        $breadcrumbs = $this->getLayout()->getBlock('breadcrumbs');

        if ($this->vendorConfig->getVendorConfig('search/general/show_bread_crumb', $this->getVendor()->getId())) {
            if ($breadcrumbs) {
                $label = $this->vendorConfig->getVendorConfig('general/store_information/name', $this->getVendor()->getId());
                if (!$label) {
                    $label = 'Home';
                }

                $breadcrumbs->addCrumb(
                    'home',
                    [
                        'label' => __($label),
                        'title' => __('Go to Vendor Page'),
                        'link' => $this->getVendorPageUrl(),
                    ]
                )->addCrumb(
                    'search',
                    ['label' => $title, 'title' => $title]
                );
            }
        } else {
            $this->getLayout()->removeOutputElement('breadcrumbs');
        }

        return parent::_prepareLayout();
    }

    /**
     * Retrieve additional blocks html.
     *
     * @return string
     */
    public function getAdditionalHtml()
    {
        return $this->getLayout()->getBlock('search_result_list')->getChildHtml('additional');
    }

    /**
     * Retrieve search list toolbar block.
     *
     * @return ListProduct
     */
    public function getListBlock()
    {
        return $this->getChildBlock('search_result_list');
    }

    /**
     * Set search available list orders.
     *
     * @return $this
     */
    public function setListOrders()
    {
        $category = $this->catalogLayer->getCurrentCategory();
        /* @var $category \Magento\Catalog\Model\Category */
        $availableOrders = $category->getAvailableSortByOptions();
        unset($availableOrders['position']);
        $availableOrders['relevance'] = __('Relevance');

        $this->getListBlock()->setAvailableOrders(
            $availableOrders
        )->setDefaultDirection(
            'desc'
        )->setDefaultSortBy(
            'relevance'
        );

        return $this;
    }

    /**
     * Set available view mode.
     *
     * @return $this
     */
    public function setListModes()
    {
        $test = $this->getListBlock();
        $test->setModes(['grid' => __('Grid'), 'list' => __('List')]);

        return $this;
    }

    /**
     * Retrieve Search result list HTML output.
     *
     * @return string
     */
    public function getProductListHtml()
    {
        return $this->getChildHtml('search_result_list');
    }

    /**
     * Retrieve loaded category collection.
     *
     * @return Collection
     */
    protected function _getProductCollection()
    {
        if (null === $this->productCollection) {
            $this->productCollection = $this->getListBlock()->getLoadedProductCollection();
        }

        return $this->productCollection;
    }

    /**
     * Get search query text.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getSearchQueryText()
    {
        $label = $this->vendorConfig->getVendorConfig('general/store_information/name', $this->getVendor()->getId());
        if (!$label) {
            $label = 'Home';
        }

        return __("%1 - Search results for: '%2'", $label, $this->catalogSearchData->getEscapedQueryText());
    }

    /**
     * Retrieve search result count.
     *
     * @return string
     */
    public function getResultCount()
    {
        if (!$this->getData('result_count')) {
            $size = $this->_getProductCollection()->getSize();
            $this->_getQuery()->saveNumResults($size);
            $this->setResultCount($size);
        }

        return $this->getData('result_count');
    }

    /**
     * Retrieve No Result or Minimum query length Text.
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getNoResultText()
    {
        if ($this->catalogSearchData->isMinQueryLength()) {
            return __('Minimum Search query length is %1', $this->_getQuery()->getMinQueryLength());
        }

        return $this->_getData('no_result_text');
    }

    /**
     * Retrieve Note messages.
     *
     * @return array
     */
    public function getNoteMessages()
    {
        return $this->catalogSearchData->getNoteMessages();
    }

    public function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }

    public function getVendorPageUrl()
    {
        return $this->vendorPageHelper->getUrl($this->getVendor());
    }
}
