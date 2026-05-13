<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Model\Coupon\Action;

class BuyXGetFreeShip extends AbstractDiscount
{
  /**
   * @param \Vnecoms\VendorsCoupon\Model\Coupon $coupon
   * @param float $maxDiscount
   * @param $address
   */
    public function calculate($coupon, $maxDiscount, $address)
    {
        $x = $coupon->getBuyX();
        $y = $coupon->getAmount();
        if (!$x || $maxDiscount < $x) {
            return false;
        }

        $shippingMethod = $address->getShippingMethod();
        $shippingMethod = str_replace('vendor_multirate_', '', $shippingMethod);
        $shippingMethods = explode(
            \Vnecoms\VendorsShipping\Plugin\Shipping::METHOD_SEPARATOR,
            $shippingMethod
        );

        $vendorShippingMethod = false;
        foreach ($shippingMethods as $method) {
            $tmpMethods = explode(
                \Vnecoms\VendorsShipping\Plugin\Shipping::SEPARATOR,
                $method
            );

            if (sizeof($tmpMethods) != 2) {
                continue;
            }
            if ($coupon->getVendorId() == $tmpMethods[1]) {
                $vendorShippingMethod = $method;
            }
        }

        if (!$vendorShippingMethod) {
            return false;
        }
        $shippingRate = $address->getShippingRateByCode($vendorShippingMethod);
        if (!$shippingRate || !$shippingRate->getId()) {
            return false;
        }
        return $shippingRate->getPrice();
    }
}
