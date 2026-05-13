<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\RMA\Controller\Guest;

class RmaViewAuthorization implements RmaViewAuthorizationInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $orderConfig;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    /**
     * {@inheritdoc}
     */
    public function canView(\Vnecoms\RMA\Model\Request $request)
    {
        $postRma = $this->customerSession->getPostRma();
        if (!$postRma) {
            return false;
        }
        if ($request->getId()
            && $request->getData("order_incremental_id")
            && $request->getData("order_incremental_id") == $postRma["order_incremental_id"]
        ) {
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function canViewOrder(\Magento\Sales\Model\Order $order)
    {
        $postRma = $this->customerSession->getPostRma();
        if (!$postRma) {
            return false;
        }
        if ($order->getId()
            && $order->getIncrementId()
            && $order->getIncrementId() == $postRma["order_incremental_id"]
        ) {
            return true;
        }
        return false;
    }
}
