<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Model\Coupon\Action;

abstract class AbstractDiscount implements DiscountInterface
{
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
    ) {
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * @param \Vnecoms\VendorsCoupon\Model\Coupon $coupon
     * @param float $maxDiscount
     * @param $address
     */
    abstract public function calculate($coupon, $maxDiscount, $address);
}
