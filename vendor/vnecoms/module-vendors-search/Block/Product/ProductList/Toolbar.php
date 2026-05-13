<?php

namespace Vnecoms\VendorsSearch\Block\Product\ProductList;

use Magento\Catalog\Helper\Product\ProductList;
use Magento\Catalog\Model\Product\ProductList\Toolbar as ToolbarModel;

class Toolbar extends \Magento\Catalog\Block\Product\ProductList\Toolbar
{
    /** @var \Magento\Store\Model\StoreManagerInterface */
  //  protected $storeManager;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $vendorPageHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Catalog\Model\Config $catalogConfig,
        ToolbarModel $toolbarModel,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        ProductList $productListHelper,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        //   \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper,
        array $data = []
    ) {
        //   $this->storeManager = $storeManager;
        $this->_coreRegistry = $registry;
        $this->vendorPageHelper = $vendorPageHelper;
        parent::__construct($context, $catalogSession, $catalogConfig, $toolbarModel, $urlEncoder, $productListHelper, $postDataHelper, $data);
    }

//    public function getPagerUrl($params = [])
//    {
//        $urlParams = [];
//        $urlParams['_current'] = true;
//        $urlParams['_escape'] = false;
//        $urlParams['_use_rewrite'] = true;
//        $urlParams['_query'] = $params;
//
//        return $this->vendorPageHelper->getUrl($this->_coreRegistry->registry('vendor'), 'searchresult', $urlParams);
//    }
}
