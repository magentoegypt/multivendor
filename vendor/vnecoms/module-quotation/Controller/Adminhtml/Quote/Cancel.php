<?php
/**
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Controller\Adminhtml\Quote;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Cancel extends \Vnecoms\Quotation\Controller\Adminhtml\Quote implements HttpGetActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Vnecoms_Quotation::cancel';

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
                $this->quoteRepository->cancel($quote);
                $this->messageManager->addSuccess(__('You canceled the quote.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(__('You have not canceled the item.'));
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            }
            return $resultRedirect->setPath('quotation/quote/view', ['quote_id' => $quote->getQuoteId()]);
        }
        return $resultRedirect->setPath('quotation/*/');
    }
}
