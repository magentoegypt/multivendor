<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Gift Wrap for Magento 2 (System)
 */

namespace Amasty\CheckoutGiftWrap\Model;

use Amasty\CheckoutGiftWrap\Api\GiftWrapInformationManagementInterface;
use Amasty\CheckoutGiftWrap\Api\GuestGiftWrapInformationManagementInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;

class GuestGiftWrapInformationManagement implements GuestGiftWrapInformationManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var GiftWrapInformationManagementInterface
     */
    protected $giftWrapInformationManagement;

    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        GiftWrapInformationManagementInterface $giftWrapInformationManagement
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->giftWrapInformationManagement = $giftWrapInformationManagement;
    }

    /**
     * @param string $cartId
     * @param bool $checked
     * @return TotalsInterface
     */
    public function update($cartId, $checked)
    {
        /** @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->giftWrapInformationManagement->update(
            $quoteIdMask->getQuoteId(),
            $checked
        );
    }
}
