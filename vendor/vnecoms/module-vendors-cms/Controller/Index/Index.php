<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /** @var \Vnecoms\VendorsConfig\Helper\Data  */
    protected $vendorConfigHelper;

    /** @var \Vnecoms\VendorsCms\Helper\Page  */
    protected $cmsHelperPage;

    /** @var \Magento\Framework\View\Result\PageFactory  */
    protected $resultPageFactory;

    protected $coreRegistry;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Vnecoms\VendorsConfig\Helper\Data $vendorConfigHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Vnecoms\VendorsCms\Helper\Page $cmsHelperPage
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        $this->vendorConfigHelper = $vendorConfigHelper;
        $this->cmsHelperPage = $cmsHelperPage;
        $this->resultPageFactory = $resultPageFactory;
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Result page cms
     *
     * @param null $coreRoute
     * @return bool|\Magento\Framework\Controller\Result\Forward|\Magento\Framework\View\Result\Page
     */
    public function execute($coreRoute = null)
    {
        $resultPage = $this->resultPageFactory->create();
        $pageId = $this->vendorConfigHelper
            ->getVendorConfig(\Vnecoms\VendorsCms\Helper\Page::XML_PATH_HOME_PAGE, $this->getVendorId());
        $resultPage = $this->cmsHelperPage->prepareResultPage($this, $pageId);
        if (!$resultPage) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->resultForwardFactory->create();
            $resultForward->forward('defaultIndex');
            return $resultForward;
        }

        return $resultPage;
    }

    /**
     * Retrieve vendor id
     *
     * @return mixed
     */
    protected function getVendorId()
    {
        if ($this->_request->getParam('vendor_id')) {
            return $this->_request->getParam('vendor_id');
        }
        return $this->coreRegistry->registry('vendor')->getId();
    }
}
