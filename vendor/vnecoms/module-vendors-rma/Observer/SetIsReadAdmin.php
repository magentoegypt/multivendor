<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Observer;

use Magento\Framework\Event\ObserverInterface;

class SetIsReadAdmin implements ObserverInterface
{
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;
    /**
     * Vendor collection
     * @var \Vnecoms\RMA\Model\Request
     */
    protected $_requestFactory;
    /**
     * PendingRmaObserver constructor.
     * @param \Vnecoms\RMA\Model\RequestFactory $requestFactory
     * @param \Magento\Framework\View\Element\Context $context
     * @param array $data
     */
    public function __construct(
        \Vnecoms\RMA\Model\RequestFactory $requestFactory,
        \Magento\Framework\View\Element\Context $context,
        array $data = []
    ) {
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_requestFactory= $requestFactory->create();
    }

    /**
     * Add the notification if there are any vendor awaiting for approval.
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $rma = $observer->getRma();
        $rma->setData("is_customer_read",0);
        $rma->setData("is_vendor_read",0);
    }

}
