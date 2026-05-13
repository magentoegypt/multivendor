<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\VendorsCms\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;
use Magento\Framework\App\Action\Forward;

/**
 * Class with class map capability
 *
 * ...
 */
class PredispatchVendorsPageObserver implements ObserverInterface
{
    /** @var \Vnecoms\VendorsCms\Helper\Page  */
    protected $_cmsPageHelper;
    /** @var Registry  */
    protected $_coreRegistry;
    /** @var \Vnecoms\VendorsCms\Model\PageFactory  */
    protected $_cmsPageFactory;
    /** @var \Magento\Framework\App\ActionFactory  */
    protected $_actionFactory;
    /** @var \Magento\Framework\App\ActionFlag  */
    protected $_actionFlag;

    /**
     * PredispatchVendorsPageObserver constructor.
     * @param Registry $coreRegistry
     * @param \Vnecoms\VendorsCms\Model\PageFactory $pageFactory
     * @param \Vnecoms\VendorsCms\Helper\Page $cmsPageHelper
     * @param \Magento\Framework\App\ActionFlag $actionFlag
     * @param \Magento\Framework\App\ActionFactory $actionFactory
     */
    public function __construct(
        Registry $coreRegistry,
        \Vnecoms\VendorsCms\Model\PageFactory $pageFactory,
        \Vnecoms\VendorsCms\Helper\Page $cmsPageHelper,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Framework\App\ActionFactory $actionFactory
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_cmsPageFactory = $pageFactory;
        $this->_cmsPageHelper = $cmsPageHelper;
        $this->_actionFlag = $actionFlag;
        $this->_actionFactory = $actionFactory;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return \Magento\Framework\App\ActionInterface
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
		    $vendor = $this->_coreRegistry->registry('vendor');
        if (!$vendor || !$vendor->getId()) return;

        /** @var \Vnecoms\VendorsCms\Model\Page $page */
        $page = $this->_cmsPageFactory->create();
        $pageIdentifier = $this->_cmsPageHelper->getVendorHomePage();
		    if (!$pageIdentifier) return;
        /** @var \Vnecoms\VendorsCms\Model\Page $vendorsCmspage */
        $vendorsCmspage = $this->_cmsPageFactory->create();
        $pageId = $vendorsCmspage->checkIdentifier($pageIdentifier, $this->getVendor()->getId());

		    if(!$pageId) return;
        // Check request vendor has cms homepage config
        if ($pageIdentifier != null) {
            $request = $observer->getRequest();
            $request->setModuleName('vendorscms')
                ->setControllerName('page')
                ->setActionName('view')
                ->setForwarded(true)
                ->setParam('vendor_id', $this->getVendor()->getId())
                ->setDispatched(false)
                ->setParam('page_id', $pageId);
            $request->setAlias(\Magento\Framework\Url::REWRITE_REQUEST_PATH_ALIAS, $pageIdentifier);
            $this->_actionFlag->set('', \Magento\Framework\App\ActionInterface::FLAG_NO_DISPATCH, true);
            if (!$this->_coreRegistry->registry('vendor_cms_page')) {
                $this->_coreRegistry->register('vendor_cms_page', true);
            }

            return $this->_actionFactory->create(Forward::class, ['request' => $request])->execute();
        }
    }

    /**
     * @return mixed|\Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }
}
