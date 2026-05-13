<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Controller\Noroute;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /** @var \Vnecoms\VendorsConfig\Helper\Data  */
    protected $vendorConfigHelper;

    /**
     * @param \Magento\Framework\App\Action\Context               $context
     * @param \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Vnecoms\VendorsConfig\Helper\Data $vendorConfigHelper,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory
    ) {
        $this->vendorConfigHelper = $vendorConfigHelper;
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context);
    }

    /**
     * Render CMS 404 Not found page.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $pageId = $this->vendorConfigHelper
            ->getVendorConfig(\Vnecoms\VendorsCms\Helper\Page::XML_PATH_HOME_PAGE, $this->getId());
        /** @var \Magento\Cms\Helper\Page $pageHelper */
        $pageHelper = $this->_objectManager->get('Vnecoms\VendorsCms\Helper\Page');
        $resultPage = $pageHelper->prepareResultPage($this, $pageId);
        if ($resultPage) {
            $resultPage->setStatusHeader(404, '1.1', 'Not Found');
            $resultPage->setHeader('Status', '404 File not found');

            return $resultPage;
        } else {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->resultForwardFactory->create();
            $resultForward->setController('index');
            $resultForward->forward('defaultNoRoute');

            return $resultForward;
        }
    }
}