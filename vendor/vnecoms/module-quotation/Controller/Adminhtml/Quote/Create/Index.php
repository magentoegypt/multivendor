<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */
namespace Vnecoms\Quotation\Controller\Adminhtml\Quote\Create;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends \Vnecoms\Quotation\Controller\Adminhtml\Quote\Create implements HttpGetActionInterface
{
    /**
     * Index page
     *
     * @return void
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magento_Sales::sales_order');
        $resultPage->getConfig()->getTitle()->prepend(__('Quotations'));
        $resultPage->getConfig()->getTitle()->prepend(__('New Quote'));
        return $resultPage;
    }
}
