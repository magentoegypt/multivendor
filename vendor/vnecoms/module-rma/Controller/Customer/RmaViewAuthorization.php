<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\RMA\Controller\Customer;

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
        $customerId = $this->customerSession->getCustomerId();
        if ($request->getId()
            && $request->getCustomerId()
            && $request->getCustomerId() == $customerId
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
        $customerId = $this->customerSession->getCustomerId();
        if ($order->getId()
            && $order->getCustomerId()
            && $order->getCustomerId() == $customerId
        ) {
            return true;
        }
        return false;
    }
}
