<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 */
namespace Vnecoms\Quotation\Controller\Quote;

use Magento\Framework\App\Action\Context;

class Success extends \Vnecoms\Quotation\Controller\Quote
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;
    
    public function __construct
    (
        Context $context,
        \Vnecoms\Quotation\Model\Session $session,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context, $session);
        $this->resultPageFactory = $resultPageFactory;
    }
    
    /**
     * Order success action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $session = $this->getSession();        
        if (!$session->getLastSuccessQuoteId() || !$session->getLastQuoteId()) {
           return $this->resultRedirectFactory->create()->setPath('quotation');
        }
        
        $session->clearQuote();

        $resultPage = $this->resultPageFactory->create();

        return $resultPage;
    }
}
