<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Observer\QuoteSubmit;

use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutDeliveryDate\Model\DeliveryDateProvider;
use Amasty\CheckoutDeliveryDate\Model\ResourceModel\Delivery;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SetOrderId implements ObserverInterface
{
    /**
     * @var Config
     */
    private $checkoutConfig;

    /**
     * @var DeliveryDateProvider
     */
    private $deliveryProvider;

    /**
     * @var Delivery
     */
    private $deliveryResource;

    public function __construct(
        Config $checkoutConfig,
        DeliveryDateProvider $deliveryProvider,
        Delivery $deliveryResource
    ) {
        $this->checkoutConfig = $checkoutConfig;
        $this->deliveryProvider = $deliveryProvider;
        $this->deliveryResource = $deliveryResource;
    }

    /**
     * 'sales_model_service_quote_submit_success' event
     *
     * @param Observer $observer
     * @return SetOrderId|void
     */
    public function execute(Observer $observer)
    {
        if (!$this->checkoutConfig->isEnabled()) {
            return $this;
        }
        /** @var  \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        if (!$order) {
            return $this;
        }

        $delivery = $this->deliveryProvider->findByQuoteId((int)$quote->getId());
        if ($delivery->getId()) {
            $delivery->setData('order_id', (int)$order->getId());
            $this->deliveryResource->save($delivery);
        }
    }
}
