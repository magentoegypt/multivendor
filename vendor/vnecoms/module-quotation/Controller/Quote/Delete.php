<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

namespace Vnecoms\Quotation\Controller\Quote;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Vnecoms\Quotation\Model\Session;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action
{
    /**
     * @var Session
     */
    protected $session;
    
    /**
     * @param Context $context
     * @param Session $session
     */
    public function __construct(
        Context $context,
        Session $session
    ) {
        $this->session = $session;
        parent::__construct($context);
    }

    
    public function execute()
    {
        $itemId = (int)$this->getRequest()->getParam('id');
        try {
            $item = $this->session->getQuote()->getItemById($itemId);
            if (!$item instanceof \Vnecoms\Quotation\Model\Item) {
                throw new LocalizedException(__('We can\'t find the quote item.'));
            }
            $itemName = $item->getName();
            $this->session->getQuote()->removeItem($itemId);
            $this->session->getQuote()->collectTotals();
            $this->session->getQuote()->save();
            $this->messageManager->addSuccess('Item %1 is removed.', $itemName);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addError('An error occurred.');
        }
        
        return $this->_redirect('quotation');
    }
}