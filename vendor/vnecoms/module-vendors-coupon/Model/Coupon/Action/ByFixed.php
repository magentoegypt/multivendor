<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Model\Coupon\Action;

class ByFixed extends AbstractDiscount
{
  /**
   * @param \Vnecoms\VendorsCoupon\Model\Coupon $coupon
   * @param float $maxDiscount
   * @param $address
   */
    public function calculate($coupon, $maxDiscount, $address)
    {
        return min($coupon->getAmount(), $maxDiscount);
    }
}
