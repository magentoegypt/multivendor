<?php

namespace Vnecoms\VendorsSearch\Block\Page\Html;

class Pager extends \Magento\Theme\Block\Html\Pager
{
    /** @var \Magento\Store\Model\StoreManagerInterface */
  //  protected $storeManager;

    protected $_coreRegistry;

    protected $vendorPageHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        //  \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsPage\Helper\Data $vendorPageHelper,
        array $data = []
    ) {
        // $this->storeManager = $storeManager;
        $this->_coreRegistry = $registry;
        $this->vendorPageHelper = $vendorPageHelper;
        parent::__construct($context, $data);
    }

//    public function getPagerUrl($params = [])
//    {
//        $urlParams = [];
//        $urlParams['_current'] = true;
//        $urlParams['_escape'] = true;
//        $urlParams['_use_rewrite'] = true;
//        $urlParams['_fragment'] = $this->getFragment();
//        $urlParams['_query'] = $params;
//
//        return $this->vendorPageHelper->getUrl($this->_coreRegistry->registry('vendor'), 'searchresult', $urlParams);
//    }
}
