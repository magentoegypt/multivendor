<?php
namespace Vnecoms\VendorsDomain\Controller\ShippingPolicies;

use Magento\Framework\Exception\NotFoundException;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;
    
    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\Vendors\Helper\Data $vendorHelper
    ) {
        parent::__construct($context);
        $this->_coreRegistry = $coreRegistry;
        $this->_vendorHelper = $vendorHelper;
    }
    
    /**
     * Display customer wishlist
     *
     * @return \Magento\Framework\View\Result\Page
     * @throws NotFoundException
     */
    public function execute()
    {
        if (!$this->_coreRegistry->registry('vendor')) {
            return $this->_forward('no-route');
        }
        $vendor = $this->_coreRegistry->registry('vendor');
        $this->_view->loadLayout();
        $storeName = $this->_vendorHelper->getVendorStoreName($vendor->getId());
        $description = $this->_vendorHelper->getVendorStoreShortDescription($vendor->getId());
        $pageConfig = $this->_view->getPage()->getConfig();
        $pageConfig->getTitle() ->set(__("%1 - Shipping Policies", $storeName));
        $pageConfig->setDescription($description);
        $this->_view->renderLayout();
    }
}
