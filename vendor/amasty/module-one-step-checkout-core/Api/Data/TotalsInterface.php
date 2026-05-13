<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */
namespace Amasty\CheckoutCore\Api\Data;

interface TotalsInterface
{
    /**
     * Constants defined for keys of data array
     */
    public const TOTALS = 'totals';
    public const SHIPPING = 'shipping';
    public const PAYMENT = 'payment';

    /**
     * @return \Magento\Quote\Api\Data\TotalsInterface
     */
    public function getTotals();

    /**
     * @return \Magento\Quote\Api\Data\ShippingMethodInterface[] An array of shipping methods.
     */
    public function getShipping();

    /**
     * @return \Magento\Quote\Api\Data\PaymentMethodInterface[] Array of payment methods.
     */
    public function getPayment();
}
