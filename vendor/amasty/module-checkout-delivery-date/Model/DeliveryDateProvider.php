<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Model;

use Amasty\CheckoutDeliveryDate\Api\Data\DeliveryInterface;
use Amasty\CheckoutDeliveryDate\Model\ResourceModel\Delivery\Collection;
use Amasty\CheckoutDeliveryDate\Model\ResourceModel\Delivery\CollectionFactory;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class DeliveryDateProvider
{
    /**
     * @var CollectionFactory
     */
    private $deliveryCollectionFactory;

    public function __construct(CollectionFactory $deliveryCollectionFactory)
    {
        $this->deliveryCollectionFactory = $deliveryCollectionFactory;
    }

    /**
     * @param int $quoteId
     * @return DeliveryInterface
     */
    public function findByQuoteId(int $quoteId): DeliveryInterface
    {
        $delivery = $this->findByField($quoteId, CartItemInterface::KEY_QUOTE_ID);

        if (!$delivery->getId()) {
            $delivery->setData(CartItemInterface::KEY_QUOTE_ID, $quoteId);
        }

        return $delivery;
    }

    /**
     * @param int $orderId
     * @return DeliveryInterface
     */
    public function findByOrderId(int $orderId): DeliveryInterface
    {
        return $this->findByField($orderId, OrderItemInterface::ORDER_ID);
    }

    /**
     * @param int $value
     * @param string $field
     * @return DeliveryInterface
     */
    public function findByField(int $value, string $field): DeliveryInterface
    {
        /** @var Collection $deliveryCollection */
        $deliveryCollection = $this->deliveryCollectionFactory->create();

        return $deliveryCollection
            ->addFieldToFilter($field, $value)
            ->getFirstItem();
    }
}
