<?php declare(strict_types=1);

namespace Mstore\Sales\Model;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config;

class OrderAuthorization implements OrderAuthorizationInterface
{
    /**
     * @var Config
     */
    private $orderConfig;

    /**
     * OrderAuthorization constructor.
     * @param Config $orderConfig
     */
    public function __construct(
        Config $orderConfig
    ) {
        $this->orderConfig = $orderConfig;
    }

    /**
     * @inheritDoc
     */
    public function canView(Order $order, $customerId)
    {
        $availableStatuses = $this->orderConfig->getVisibleOnFrontStatuses();
        if ($order->getId()
            && $order->getCustomerId()
            && $order->getCustomerId() == $customerId
            && in_array($order->getStatus(), $availableStatuses, true)
        ) {
            return true;
        }
        return false;
    }
}
