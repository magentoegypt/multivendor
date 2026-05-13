<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\Quote\Model\CustomerManagement;

use Magento\Quote\Model\CustomerManagement;
use Magento\Quote\Model\Quote;

/**
 * Fix submit virtual checkout with required custom attributes for logged customer.
 *
 * Magento's validation gets customer shipping address even if it is a virtual quote.
 * Customer shipping address may be without newly created customer address attributes.
 * Which causes validation errors.
 *
 * @see \Magento\Quote\Model\CustomerManagement::validateAddresses
 */
class VirtualCustomAttribute
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeValidateAddresses(CustomerManagement $subject, Quote $quote): void
    {
        if ($quote->isVirtual() && $quote->getCustomerId()) {
            $billingAddress = $quote->getBillingAddress();
            if (!$billingAddress->getCustomerId() || !$billingAddress->getCustomerAddressId()) {
                return;
            }
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setSameAsBilling(true);
            $shippingAddress->setCustomerAddressId($billingAddress->getCustomerAddressId());
        }
    }
}
