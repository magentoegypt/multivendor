<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Plugin\Sales\Block\Items\AbstractItems;

use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutDeliveryDate\Block\Sales\Order\Email\Delivery;
use Magento\Sales\Block\Items\AbstractItems;

class AddDeliveryInfo
{
    /**
     * @var Config
     */
    private $checkoutConfig;

    public function __construct(Config $checkoutConfig)
    {
        $this->checkoutConfig = $checkoutConfig;
    }

    /**
     * @param AbstractItems $subject
     * @param string $result
     * @return string
     */
    public function afterToHtml(
        AbstractItems $subject,
        $result
    ) {
        if (!$this->checkoutConfig->isEnabled()) {
            return $result;
        }
        foreach ($subject->getLayout()->getUpdate()->getHandles() as $handle) {
            if (substr($handle, 0, 12) !== 'sales_email_') {
                return $result;
            }
            /** @var  \Magento\Sales\Model\Order $order */
            $order = $subject->getOrder();
            if (!$order || !$order->getId()) {
                return $result;
            }

            $deliveryBlock = $subject->getLayout()
                ->createBlock(
                    Delivery::class,
                    'amcheckout.delivery',
                    [
                        'data' => [
                            'order_id' => $order->getId()
                        ]
                    ]
                );

            $result = $deliveryBlock->toHtml() . $result;
        }

        return $result;
    }
}
