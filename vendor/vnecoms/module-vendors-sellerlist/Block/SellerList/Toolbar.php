<?php
namespace Vnecoms\VendorsSellerList\Block\SellerList;

use Magento\Catalog\Helper\Product\ProductList;
use Magento\Catalog\Model\Product\ProductList\Toolbar as ToolbarModel;
use Vnecoms\VendorsSellerList\Model\SellerList\Toolbar as SellerToolbarModel;
use Vnecoms\Vendors\Model\Vendor;

class Toolbar extends \Magento\Catalog\Block\Product\ProductList\Toolbar
{

    /** @var   */
    protected $sellerCollection;

    /** @var \Vnecoms\Vendors\Model\ResourceModel\Vendor\CollectionFactory  */
    protected $vendorCollectionFactory;

    /**
     * @var \Vnecoms\VendorsSellerList\Helper\Data
     */
    protected $_sellerListHelper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $_pageHelper;

    /**
     * @var SellerToolbarModel
     */
    protected $_sellerToolbarModel;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Catalog\Model\Config $catalogConfig,
        ToolbarModel $toolbarModel,
        SellerToolbarModel $sellerToolbarModel,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        ProductList $productListHelper,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Vnecoms\Vendors\Model\ResourceModel\Vendor\CollectionFactory $vendorCollectionFactory,
        \Vnecoms\VendorsSellerList\Helper\Data $sellerListHelper,
        \Vnecoms\VendorsPage\Helper\Data $pageHelper,
        \Magento\Framework\Registry $registry,
        array $data = []
    )
    {
        parent::__construct($context, $catalogSession, $catalogConfig, $toolbarModel, $urlEncoder, $productListHelper, $postDataHelper, $data);
        $this->vendorCollectionFactory = $vendorCollectionFactory;
        $this->_sellerListHelper = $sellerListHelper;
        $this->_pageHelper = $pageHelper;
        $this->_coreRegistry = $registry;
        $this->_sellerToolbarModel = $sellerToolbarModel;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPerPageValue()
    {
        return $this->_sellerListHelper->getDefaultLimitPerPageValue('');
    }

    /**
     * Set collection to pager
     *
     * @param \Magento\Framework\Data\Collection|\Vnecoms\Vendors\Model\ResourceModel\Vendor\Collection $collection
     * @return $this
     */
    public function setCollection($collection)
    {
        $this->_collection = $collection;

        $this->_collection->setCurPage($this->getCurrentPage());

        // we need to set pagination only if passed value integer and more that 0
        $limit = (int)$this->getLimit();
        if ($limit) {
            $this->_collection->setPageSize($limit);
        }

        return $this;
    }

    /**
     * Check is Expanded
     *
     * @return bool
     */
    public function isExpanded()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getWidgetOptionsJson(array $customOptions = [])
    {
        //$defaultMode = $this->_productListHelper->getDefaultViewMode($this->getModes());
        $options = [
            'limit' => 'seller_list_limit',
            'limitDefault' => $this->_sellerListHelper->getDefaultLimitPerPageValue(''),
            'url' => $this->getPagerUrl(),
        ];
        $options = array_replace_recursive($options, $customOptions);
        return json_encode(['sellerListToolbarForm' => $options]);
    }


    /**
     * {@inheritdoc}
     */
    public function getAvailableLimit()
    {
        return $this->_sellerListHelper->getAvailableLimit();
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

        $limits = $this->getAvailableLimit();
        $defaultLimit = $this->getDefaultPerPageValue();
        if (!$defaultLimit || !isset($limits[$defaultLimit])) {
            $keys = array_keys($limits);
            $defaultLimit = $keys[0];
        }
        //echo 'default: '.$defaultLimit;
        $limit = $this->_sellerToolbarModel->getLimit();
        if (!$limit || !isset($limits[$limit])) {
            $limit = $defaultLimit;
        }

        if ($limit != $defaultLimit) {
            $this->_memorizeParam('limit_page', $limit);
        }

        $this->setData('_current_limit', $limit);
        return $limit;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Magento\Catalog\Block\Product\ProductList\Toolbar::getPagerHtml()
     */
    public function getPagerHtml() {
        //return '';
        $pagerBlock = $this->getChildBlock('seller_list_toolbar_pager');
        if ($pagerBlock instanceof \Magento\Framework\DataObject) {

            /* @var $pagerBlock \Magento\Theme\Block\Html\Pager */
            $pagerBlock->setAvailableLimit($this->getAvailableLimit());

            $pagerBlock->setUseContainer(
                true
            )->setShowPerPage(
                false
            )->setShowAmounts(
                false
            )->setFrameLength(
                $this->_scopeConfig->getValue(
                    'design/pagination/pagination_frame',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->setJump(
                $this->_scopeConfig->getValue(
                    'design/pagination/pagination_frame_skip',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->setLimit(
                $this->getLimit()
            )->setCollection(
                $this->getCollection()
            );

            return $pagerBlock->toHtml();
        }

        return '';
    }
    
    /**
     * Get Vendor object
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor(){
        return $this->_coreRegistry->registry('vendor');
    }
    
    /**
     * Can show view all items button
     * 
     * @return boolean
     */
    public function canShowViewAllItemsButton(){
        return $this->getTotalNum() > $this->getLimit();
    }
    
    /**
     * Get view all items url
     * 
     * @return string
     */
    public function getViewAllItemsUrl(){
        return $this->_pageHelper->getUrl($this->getVendor(),'items');
    }
    
}
