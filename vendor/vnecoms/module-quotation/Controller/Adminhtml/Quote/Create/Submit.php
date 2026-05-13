<?php

namespace Vnecoms\Quotation\Controller\Adminhtml\Quote\Create;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Vnecoms\Quotation\Model\Quote;

class Submit extends \Vnecoms\Quotation\Controller\Adminhtml\Quote\Create implements HttpPostActionInterface
{
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $request = $this->getRequest();
            $quote = $this->_objectManager->create('Vnecoms\Quotation\Model\Quote');
            $quote->setIsActive(true);
            $quote->setStoreId($request->getParam('store_id'));
            $quote->addData($this->getRequest()->getParams());
            $quote->setStatus(Quote::STATUS_CREATED_NOT_SENT);
            if(!$quote->getCustomerId()) $quote->setCustomerId(null);
            $this->quoteRepository->save($quote);

            $resultRedirect->setPath('quotation/quote/view', ['quote_id' => $quote->getId()]);

        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $message = $e->getMessage();
            if (!empty($message)) {
                $this->messageManager->addError($message);
            }
            $resultRedirect->setPath('quotation/quote_create/');
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('Quote saving error: %1', $e->getMessage()));
            $resultRedirect->setPath('quotation/quote_create/');
        }
        return $resultRedirect;
    }
}
