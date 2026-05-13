<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

namespace Vnecoms\Quotation\Controller\Quote;

use Magento\Backend\App\Action;

class Accept extends Action
{
    protected $quoteRepository;

    public function __construct
    (
        Action\Context $context,
        \Vnecoms\Quotation\Api\QuoteRepositoryInterface $quoteRepository
    )
    {
        parent::__construct($context);
        $this->quoteRepository = $quoteRepository;
    }

    public function execute()
    {
        try {
            $quoteId = $this->getRequest()->getParam('quote_id');
            $quote = $this->quoteRepository->getById($quoteId);

            $this->quoteRepository->accept($quote);
            $this->messageManager->addSuccess(__('You accepted this quote'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError(
                $this->_objectManager->get('Magento\Framework\Escaper')->escapeHtml($e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t accepted the quote.'));
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
        }
    }
}