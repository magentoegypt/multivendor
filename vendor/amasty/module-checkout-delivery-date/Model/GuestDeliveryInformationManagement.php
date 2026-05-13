<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Model;

use Amasty\CheckoutDeliveryDate\Api\DeliveryInformationManagementInterface;
use Amasty\CheckoutDeliveryDate\Api\GuestDeliveryInformationManagementInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class GuestDeliveryInformationManagement implements GuestDeliveryInformationManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    private $quoteIdMaskFactory;

    /**
     * @var DeliveryInformationManagementInterface
     */
    private $deliveryInformationManagement;

    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        DeliveryInformationManagementInterface $deliveryInformationManagement
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;

        $this->deliveryInformationManagement = $deliveryInformationManagement;
    }

    /**
     * @param string $cartId
     * @param string $date
     * @param int $time
     * @param string $comment
     * @return bool
     */
    public function update($cartId, $date, $time = -1, $comment = ''): bool
    {
        /** @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        return $this->deliveryInformationManagement->update(
            $quoteIdMask->getQuoteId(),
            $date,
            $time,
            $comment
        );
    }
}
