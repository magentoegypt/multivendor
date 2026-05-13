<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Guest;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class ListAction extends \Magento\Framework\App\Action\Action
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
     * @var \Vnecoms\RMA\Helper\Config
     */
    protected $_config;
    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Customer\Model\Session $session
     * @param \Vnecoms\RMA\Helper\Config $config
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Customer\Model\Session $session,
        \Vnecoms\RMA\Helper\Config $config
    ) {
        $this->resultPageFactory    = $resultPageFactory;
        $this->_session             = $session;
        $this->_urlinterface        = $context->getUrl();
        $this->_config              = $config;
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

        if ($this->_session->getPostRma()) {
            $data = $this->_session->getPostRma();
            /** @var \Magento\Framework\View\Result\Page $resultPage */
            if ($this->_session->isLoggedIn() || !$this->_config->allowGuestsRequest()) {
                return $resultRedirect->setPath('rma/customer');
            } else {
                $resultPage = $this->resultPageFactory->create();
            }
            $resultPage->getConfig()->getTitle()->set(__('RMA - Order #').$data['order_incremental_id']);
            return $resultPage;
        }
        return $resultRedirect->setPath('rma/guest');
    }
}
