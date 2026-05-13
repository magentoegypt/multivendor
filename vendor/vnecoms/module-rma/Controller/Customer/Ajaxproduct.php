<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Registry;

class Ajaxproduct extends \Magento\Customer\Controller\AbstractAccount
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var RmaViewAuthorizationInterface
     */
    protected $rmaAuthorization;

    /**
     * Ajaxproduct constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Registry $registry
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Registry $registry,
        RmaViewAuthorizationInterface $rmaAuthorization
    ) {
        $this->rmaAuthorization = $rmaAuthorization;
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $registry;
        parent::__construct($context);
    }

    /**
     * @return void
     */

    public function execute()
    {
        $orderId= $this->getRequest()->getParam('order', 0);
        $order = $this->_getOrderByIncrementId($orderId);

        if (!$this->rmaAuthorization->canViewOrder($order)) {
            $this->_view->loadLayout();
            $this->_view->renderLayout();
            return;
        }

        if ($order->getState() == \Magento\Sales\Model\Order::STATE_PROCESSING ||
            $order->getState() == \Magento\Sales\Model\Order::STATE_COMPLETE
        ) {
            $this->_view->loadLayout();
            $this->_coreRegistry->register('current_order', $order);
            $this->_view->renderLayout();
        }
    }

    /**
     * @param $incrementId
     * return order object
     */
    protected function _getOrderByIncrementId($incrementId)
    {
        // get Order from sales_order table
        $order = $this->_objectManager->create('Magento\Sales\Model\Order')->load($incrementId, "increment_id");
        return $order;
    }
}
