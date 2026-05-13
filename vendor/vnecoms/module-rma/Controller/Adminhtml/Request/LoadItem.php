<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Adminhtml\Request;

class LoadItem extends Request
{
    /**
     * @return void
     */
    public function execute()
    {
        $orderId= $this->getRequest()->getParam('increment_id', 0);
        $order = $this->_getOrderByIncrementId($orderId);
        if ($order->getState() == \Magento\Sales\Model\Order::STATE_PROCESSING ||
            $order->getState() == \Magento\Sales\Model\Order::STATE_COMPLETE
        ) {
            $this->_view->loadLayout();
            $this->_coreRegistry->register('current_order', $order);
            $this->_view->renderLayout();
        } else {
            $this->getResponse()->setBody("false");
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
