<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Model\Coupon\Action;

class BuyXGetYFixed extends AbstractDiscount
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
        return min($y, $maxDiscount);
    }
}
