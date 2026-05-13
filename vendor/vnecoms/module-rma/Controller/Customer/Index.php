<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Index extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_session;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlinterface;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Customer\Model\Session $session
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $session
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_session = $session;
        $this->_urlinterface = $context->getUrl();
        parent::__construct($context);
    }

    /**
     * Customer order history
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        if ($this->_session->isLoggedIn()) {
            $resultPage = $this->resultPageFactory->create();
        } else {
            $this->_session->setBeforeAuthUrl($this->_urlinterface->getCurrentUrl());
            return $resultRedirect->setPath('customer/account/login');
        }
        $resultPage->getConfig()->getTitle()->set(__('Request RMA'));
        return $resultPage;
    }
}
