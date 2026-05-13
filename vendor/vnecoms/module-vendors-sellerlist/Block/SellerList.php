<?php
/**
 *
 * Created by Vnecoms Core Team.
 *
 * @category  Vnecoms
 * @package   Vnecoms_ModuleName
 * @author    Vnecoms
 * @created_by mrtuvn
 * @date: 28/04/2017
 * @time: 17:13
 * @copyright Copyright (c) 2012-2017 Vnecoms
 * @license   https://www.vnecoms.com
 */


namespace Vnecoms\VendorsSellerList\Block;

use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\View\Element\Template;
use Vnecoms\Vendors\Model\Vendor;

class SellerList extends \Magento\Framework\View\Element\Template
{
    protected $_template = 'Vnecoms_VendorsSellerList::sellerlist.phtml';

    /**
     * Default toolbar block name
     *
     * @var string
     */
    protected $_defaultToolbarBlock = 'Vnecoms\VendorsSellerList\Block\SellerList\Toolbar';

    /**
     * @var \Vnecoms\Vendors\Model\ResourceModel\Vendor\Collection
     */
    protected $sellerCollection;


    /** @var \Vnecoms\Vendors\Model\ResourceModel\Vendor\CollectionFactory  */
    protected $vendorCollectionFactory;

    /** @var  \Vnecoms\VendorsSellerList\Helper\Data */
    protected $sellerListHelper;

    /**
     * @var \Magento\Framework\Url\Helper\Data
     */
    protected $urlHelper;

    /** @var \Vnecoms\Vendors\Helper\Data  */
    protected $vendorHelper;

    /** @var \Vnecoms\VendorsPage\Helper\Data  */
    protected $vendorPageHelper;

    /** @var \Vnecoms\VendorsConfig\Helper\Data  */
    protected $configHelper;

    /** @var \Magento\Store\Model\StoreManagerInterface  */
    protected $_storeManager;

    /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory  */
    protected $productCollectionFactory;

    /** @var \Magento\Framework\Module\Manager  */
    protected $moduleManager;

    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $_vendorSession;

    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $_vendorFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    
    /**
     * @var array
     */
    protected $productCountByVendor;

    /**
     * @param Template\Context $context
     * @param \Vnecoms\Vendors\Model\ResourceModel\Vendor\CollectionFactory $vendorCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Vnecoms\VendorsConfig\Helper\Data $configHelper
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Vnecoms\VendorsSellerList\Helper\Data $sellerListHelper
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Vnecoms\Vendors\Model\Session $vendorSession
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Vnecoms\Vendors\Model\ResourceModel\Vendor\CollectionFactory $vendorCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Vnecoms\VendorsConfig\Helper\Data $configHelper,
        \Magento\Framework\Module\Manager $moduleManager,
        \Vnecoms\VendorsSellerList\Helper\Data $sellerListHelper,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ){
        parent::__construct($context, $data);
        $this->vendorHelper = $vendorHelper;
        $this->vendorPageHelper = $vendorPageHelper;
        $this->vendorCollectionFactory = $vendorCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->urlHelper = $urlHelper;
        $this->configHelper = $configHelper;
        $this->moduleManager = $moduleManager;
        $this->sellerListHelper = $sellerListHelper;
        $this->_storeManager = $context->getStoreManager();
        $this->_vendorSession = $vendorSession;
        $this->_vendorFactory = $vendorFactory;
        $this->_coreRegistry = $coreRegistry;
    }

    /**
     * @param $seller
     * @return string
     */
    public function getSellerPageUrl($seller)
    {
        return $this->vendorPageHelper->getUrl($seller->getVendorId());
    }


    /**
     * @param \Vnecoms\Vendors\Model\Vendor|array|null $seller
     * @return string
     */
    public function getSellerImageUrl($seller)
    {
        if($seller) {
            //var_dump($seller->getData());die;
            $scopeConfig = $this->configHelper->getVendorConfig(
                'general/store_information/logo',
                $seller->getId()
            );

            if($scopeConfig  == '') {
                $url = $this->getViewFileUrl('Vnecoms_VendorsSellerList::images/noimage.jpg');
            } else {

                $basePath = 'ves_vendors/logo/';
                $logoPath = $basePath.$scopeConfig;

                $url = $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).$logoPath;


            }
            return $url;
        }
        return '';
    }

    /**
     * @param \Vnecoms\Vendors\Model\Vendor $seller
     * @return int
     */
    public function getProductCount(\Vnecoms\Vendors\Model\Vendor $seller)
    {
        if($this->productCountByVendor == null){
            $collection = $this->productCollectionFactory->create()
                ->addAttributeToFilter('vendor_id',['in' => $this->getSellerCollection()->getAllIds()])
                ->addAttributeToFilter('approval',\Vnecoms\VendorsProduct\Model\Source\Approval::STATUS_APPROVED)
                ->addMinimalPrice()
                ->addFinalPrice()
                ->addTaxPercents()
                ->setVisibility([Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH]);
            $collection->getSelect()->columns(['vendor_products_count' => 'count(e.entity_id)'])->group('vendor_id');
            $this->productCountByVendor = [];
            foreach($collection->getData() as $product){
                $this->productCountByVendor[$product['vendor_id']] = $product['vendor_products_count'];
            }
        }
        return isset($this->productCountByVendor[$seller->getId()])?$this->productCountByVendor[$seller->getId()]:0;
    }

    /**
     * @param \Vnecoms\Vendors\Model\Vendor|array|null $seller
     * @return string
     */
    public function getReviewSellerHtml($seller)
    {

        $html = '';

        if (!$this->isVendorsReviewEnabled()) {
            return '';
        } else {
            $ratingValue = $this->getRating($seller);
            if ($ratingValue == 0) {
                return $html = '';
            }
            $html .= '<div class="seller-rating rating-summary">';
            $html .= '<div class="rating-result" title="'.$ratingValue.'%">
                <span style="width: '.$ratingValue.'%"><span><span itemprop="'.$ratingValue.'"> "'.$ratingValue.'" </span>% of <span itemprop="bestRating">100</span></span></span>
            </div>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Get vendor rating
     * @param \Vnecoms\Vendors\Model\Vendor $seller
     * @return int
     */
    public function getRating($seller){
        if ($this->isVendorsReviewEnabled()) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();
            $reviewModel = $om->create('Vnecoms\VendorsReview\Model\Review');

            $reviewResource = $reviewModel->getResource();
            $rating = $reviewResource->getAverageRating($seller->getId());
            return round($rating*100/5);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPerPageValue()
    {
        return $this->sellerListHelper->getDefaultLimitPerPageValue('');
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableLimit()
    {
        return $this->sellerListHelper->getAvailableLimit();
    }

    /**
     * {@inheritdoc}
     */
    public function getLimit()
    {
        $limit = $this->_getData('_current_limit');
        if ($limit) {
            return $limit;
        }

        $defaultLimit = $this->getDefaultPerPageValue();

        $limit = $defaultLimit;

        $this->setData('_current_limit', $limit);
        return $limit;
    }

    /**
     * @return bool
     */
    protected function isVendorsReviewEnabled()
    {
        return $this->moduleManager->isEnabled('Vnecoms_VendorsReview');
    }

    /**
     * Get search query
     * @return \Magento\Framework\App\mixed
     */
    public function getSearchQuery(){
        $searchText = $this->getRequest()->getParam('seller_query');
        return $searchText ? trim($searchText) : '';
    }

    /**
     * @return mixed
     */
    protected function getSellerCollection()
    {
        if ($this->sellerCollection === null) {
            $currentWebsiteId = $this->_storeManager->getStore()->getWebsiteId();

            $this->sellerCollection = $this->vendorCollectionFactory->create()
                ->addFieldToFilter('status', Vendor::STATUS_APPROVED);

            $limit = (int)$this->getLimit();
            if ($limit) {
                $this->sellerCollection->setPageSize($limit);
            }
            if ($currentPage = (int) $this->getRequest()->getParam('p')) {
                $this->sellerCollection->setCurPage($currentPage);
            }

            $this->sellerCollection->getSelect()->where(
                "customer.website_id = ?", $currentWebsiteId
            );

            if($query = $this->getSearchQuery()){
                $this->sellerCollection->joinTable(
                    ['vendor_config' => $this->sellerCollection->getTable('ves_vendor_config')],
                    'vendor_id=entity_id',
                    ['vendor_title' => 'value'],
                    'vendor_config.path like "general/store_information/name"',
                    'left'
                );
                $this->sellerCollection->addFieldToFilter(
                    [
                        ['attribute' => 'vendor_id', 'like' => '%'.$query.'%'],
                        ['attribute' => 'vendor_title', 'like' => '%'.$query.'%'],
                    ]
                );
            }
        }
        return $this->sellerCollection;
    }

    /**
     * @return mixed
     */
    public function getRegisteredSellerCollection()
    {
        return $this->getSellerCollection();
    }

    /**
     * @param \Vnecoms\Vendors\Model\Vendor $seller
     * @return string|null
     */
    public function getSellerStoreName($seller)
    {
        return $this->vendorHelper->getVendorStoreName($seller->getId());
    }

    /**
     * @param \Vnecoms\Vendors\Model\Vendor|array|null  $seller
     * @return string
     */
    public function getSellerItemsUrl($seller)
    {
        return $this->vendorPageHelper->getUrl($seller,'items',[]);
    }

    
    /**
     * Retrieve Toolbar block
     *
     * @return \Magento\Catalog\Block\Product\ProductList\Toolbar
     */
    public function getToolbarBlock()
    {
        $blockName = $this->getToolbarBlockName();
        if ($blockName) {
            $block = $this->getLayout()->getBlock($blockName);
            if ($block) {
                return $block;
            }
        }
        $block = $this->getLayout()->createBlock($this->_defaultToolbarBlock, uniqid(microtime()));
        return $block;
    }

    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * Get loadmore URL
     * 
     * @return string
     */
    public function getLoadMoreUrl(){
        return $this->getUrl('sellerlist',['seller_query' => $this->getRequest()->getParam('seller_query')]);
    }
    
    /**
     * Get search URL
     *
     * @return string
     */
    public function getSearchUrl(){
        return $this->getUrl('sellerlist/ajaxsearch_result/index');
    }    
}