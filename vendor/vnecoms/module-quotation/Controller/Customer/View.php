<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\Quotation\Controller\Customer;

use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Vnecoms\Quotation\Model\QuoteRepository;

use Magento\Framework\App\Action\Context;

class View extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    
    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param QuoteRepository $quoteRepository
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Controller\Result\ForwardFactory $forwardFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        QuoteRepository $quoteRepository,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Controller\Result\ForwardFactory $forwardFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->quoteRepository = $quoteRepository;
        $this->_coreRegistry = $registry;
        $this->customerSession = $customerSession;
        $this->resultForwardFactory = $forwardFactory;
    }

    protected function _initQuote()
    {
        $quoteId = $this->getRequest()->getParam('quote_id',false);
        if (!$quoteId) {
            return false;
        }

        try {
            $quote = $this->quoteRepository->getById($quoteId);
        } catch (NoSuchEntityException $e) {
            return false;
        }

        $this->_coreRegistry->register('current_quote', $quote);

        return $quote;
    }


    public function execute()
    {
        $quote = $this->_initQuote();
        if(
            !$quote ||
            !$quote->getId() ||
            ($quote->getCustomerId() != $this->customerSession->getCustomerId())
        ) {
            $this->messageManager->addError(__("The quote is not available."));
            return $this->_redirect('quotation/customer');
        }
        
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        /** @var \Magento\Framework\View\Element\Html\Links $navigationBlock */
        $navigationBlock = $resultPage->getLayout()->getBlock('customer_account_navigation');
        if ($navigationBlock) {
            $navigationBlock->setActive('quotation/customer');
        }

        if ($quote) {
            $resultPage->getConfig()->getTitle()->set(__('My Quote #%1', $quote->getIncrementId()));
            return $resultPage;
        } elseif (!$this->getResponse()->isRedirect()) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }
    }
}