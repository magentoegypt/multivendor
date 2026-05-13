<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Plugin\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Address;

use Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Address\PaymentMethod;
use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote\Address;

/**
 * Fix Magento Advanced Sales Rules.
 * Copy payment method to address for filter sales rules with payment method condition.
 *
 * @since 3.0.5
 */
class PaymentMethodPlugin
{
    /**
     * @param PaymentMethod $subject
     * @param DataObject|Address $quoteAddress
     */
    public function beforeGenerateFilterText(
        PaymentMethod $subject,
        DataObject $quoteAddress
    ): void {
        if (!$quoteAddress->getPaymentMethod() && $quote = $quoteAddress->getQuote()) {
            $quoteAddress->setPaymentMethod($quote->getPayment()->getMethod());
        }
    }
}
