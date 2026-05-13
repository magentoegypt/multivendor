<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\RMA\Controller\Customer;

interface RmaViewAuthorizationInterface
{
    /**
     * Check if rma can be viewed by user
     *
     * @param \Vnecoms\RMA\Model\Request $request
     * @return bool
     */
    public function canView(\Vnecoms\RMA\Model\Request $request);


    /**
     * Check if order can be viewed by user
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function canViewOrder(\Magento\Sales\Model\Order $order);
}
