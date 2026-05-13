<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Guest;

use Magento\Sales\Model\Order;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class NewAction extends \Magento\Framework\App\Action\Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_session;
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_order;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
    /**
     * @var \Vnecoms\RMA\Helper\Config
     */
    protected $_rmaConfig;

    /**
     * NewAction constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Customer\Model\Session $session
     * @param Order $order
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Vnecoms\RMA\Helper\Config $config
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Customer\Model\Session $session,
        \Magento\Sales\Model\Order $order,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Vnecoms\RMA\Helper\Config $config
    ) {
        $this->resultPageFactory    = $resultPageFactory;
        $this->_coreRegistry        = $coreRegistry;
        $this->_session             = $session;
        $this->_urlinterface        = $context->getUrl();
        $this->_order               = $order;
        $this->_date                = $date;
        $this->_rmaConfig           = $config;
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
            /** @var \Magento\Framework\View\Result\Page $resultPage */
            $resultPage = $this->resultPageFactory->create();
            $this->_coreRegistry->register('request_rma', $this->_session->getPostRma());
            $resultPage->getConfig()->getTitle()->set(__('New Request'));
            return $resultPage;
        }
        return $resultRedirect->setPath('rma/guest');
    }
}
