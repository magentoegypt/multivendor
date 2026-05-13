<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Controller\Adminhtml\Quote;

use Magento\Framework\App\Action\HttpGetActionInterface;

class Index extends \Magento\Backend\App\Action implements HttpGetActionInterface
{

    protected $resultPageFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__("Quotations"));
        return $resultPage;
    }
}
