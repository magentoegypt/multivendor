<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Controller\Adminhtml\Quote\Create;

use Magento\Framework\App\Action\HttpPostActionInterface;

class Save extends \Vnecoms\Quotation\Controller\Adminhtml\Quote\Create implements HttpPostActionInterface
{
    /**
     * Saving as draft quote
     *
     * @return \Magento\Backend\Model\View\Result\Forward|\Magento\Backend\Model\View\Result\Redirect
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $this->_processActionData('save');

            $quote = $this->_getSession()->getQuote();
            $this->_getSession()->clearStorage();
            $this->messageManager->addSuccess(__('You created the quote.'));
           // $resultRedirect->setPath('quotation/quote/view', ['quote_id' => $quote->getId()]);
            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/view', ['quote_id' => $quote->getId()]);
            }
            return $resultRedirect->setPath('*/*/');
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $message = $e->getMessage();
            if (!empty($message)) {
                $this->messageManager->addError($message);
            }
            $resultRedirect->setPath('quotation/*/');
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Quote saving error: %1', $e->getMessage()));
            $resultRedirect->setPath('quotation/*/');
        }
        return $resultRedirect;
    }
}
