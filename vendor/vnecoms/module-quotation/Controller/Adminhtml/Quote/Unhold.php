<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Controller\Adminhtml\Quote;

use Magento\Framework\App\Action\HttpPostActionInterface;

class Unhold extends \Vnecoms\Quotation\Controller\Adminhtml\Quote implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Vnecoms_Quotation::unhold';

    /**
     * Unhold quote
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $quote = $this->_initQuote();
        if ($quote) {
            try {
                if (!$quote->canUnhold()) {
                    throw new \Magento\Framework\Exception\LocalizedException(__('Can\'t un hold quote.'));
                }
                $this->quoteRepository->unHold($quote);
                $this->messageManager->addSuccess(__('You released the quote from holding status.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(__('The quote was not on hold.'));
            }
            $resultRedirect->setPath('quotation/*/view', ['quote_id' => $quote->getQuoteId()]);
            return $resultRedirect;
        }
        $resultRedirect->setPath('quotation/*/');
        return $resultRedirect;
    }
}
