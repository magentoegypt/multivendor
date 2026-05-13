<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Controller\Page;

class View extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /** @var \Vnecoms\VendorsCms\Model\PageFactory  */
    protected $pageFactory;

    /** @var \Magento\Framework\Registry  */
    protected $_coreRegistry;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\VendorsCms\Model\PageFactory $pageFactory
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        $this->pageFactory = $pageFactory;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * View CMS page action.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $pageId = $this->getRequest()->getParam('page_id', $this->getRequest()->getParam('id', false));
        $resultPage = $this->_objectManager->get('Vnecoms\VendorsCms\Helper\Page')->prepareResultPage($this, $pageId);
        if ($this->_coreRegistry->registry('vendorscms_page_id') != null) {
            $this->_coreRegistry->register('vendorscms_page_id', $pageId);
        }
        if (!$resultPage) {
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }

        return $resultPage;
    }
}
