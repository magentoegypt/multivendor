<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Controller\Adminhtml\Quote;

use Magento\Framework\App\Action\HttpGetActionInterface;

class View extends \Vnecoms\Quotation\Controller\Adminhtml\Quote implements HttpGetActionInterface
{
    /**
     * Edit action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $quote = $this->_initQuote();

        if(!$quote || !$quote->getId()){
            return $this->resultRedirectFactory->create()->setPath('quotation/quote');
        }
        // 5. Build edit form
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->addBreadcrumb(
            $quote->getQuoteId() ? __('Edit Quote') : __('New Quote'),
            $quote->getQuoteId() ? __('Edit Quote') : __('New Quote')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Quotes'));
        $resultPage->getConfig()->getTitle()->prepend(__("Quote #%1", $quote->getIncrementId()));
        return $resultPage;
    }
}
