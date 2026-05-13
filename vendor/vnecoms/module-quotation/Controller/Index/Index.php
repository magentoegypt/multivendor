<?php
/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */


namespace Vnecoms\Quotation\Controller\Index;

use Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Vnecoms\Quotation\Model\Session
     */
    protected $session;
    
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Vnecoms\Quotation\Model\Session $session
     */
    public function __construct(
        Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Vnecoms\Quotation\Model\Session $session
    ){
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->session = $session;
    }

    /**
     * Checkout page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if($this->session->getQuote()->getId()){
            $this->session->getQuote()
                ->setTotalsCollectedFlag(false)
                ->collectTotals()->save();
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Quotes Request'));
        return $resultPage;
    }
}
