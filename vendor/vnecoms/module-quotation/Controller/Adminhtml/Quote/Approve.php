<?php

namespace Vnecoms\Quotation\Controller\Adminhtml\Quote;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Approve extends \Vnecoms\Quotation\Controller\Adminhtml\Quote implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Vnecoms_Quotation::approve';

    /**
     * Cancel order
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $quote = $this->_initQuote();
        if ($quote) {
            try {
                $this->quoteRepository->approve($quote);
                $this->messageManager->addSuccess(__('The quote is approved and sent back to customer.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(__('There is problem occursed.'));
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            }
            return $resultRedirect->setPath('quotation/quote/view', ['quote_id' => $quote->getQuoteId()]);
        }
        return $resultRedirect->setPath('quotation/*/');
    }
}
