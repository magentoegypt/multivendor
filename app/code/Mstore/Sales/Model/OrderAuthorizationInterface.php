<?php

namespace Mstore\Sales\Model;

use Magento\Sales\Model\Order;

interface OrderAuthorizationInterface
{
    /**
     * Check if order can be viewed by user
     *
     * @param Order $order
     * @param int $customerId
     * @return bool
     */
    public function canView(Order $order, $customerId);
}
