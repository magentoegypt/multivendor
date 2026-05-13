<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Api;

use Magento\Quote\Api\Data\AddressInterface;

/**
 * @api
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @codingStandardsIgnoreStart
 */
interface GuestQuoteManagementInterface
{
    /**
     * @param string $cartId
     * @param AddressInterface|null $shippingAddressFromData
     * @param AddressInterface|null $newCustomerBillingAddress
     * @param string|null $selectedPaymentMethod
     * @param string|null $selectedShippingRate
     * @param string|null $validatedEmailValue
     *
     * @return boolean
     */
    public function saveInsertedInfo(
        $cartId,
        AddressInterface $shippingAddressFromData = null,
        AddressInterface $newCustomerBillingAddress = null,
        $selectedPaymentMethod = null,
        $selectedShippingRate = null,
        $validatedEmailValue = null
    );
}
