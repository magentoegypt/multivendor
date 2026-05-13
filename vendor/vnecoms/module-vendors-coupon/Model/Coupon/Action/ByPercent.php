<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Model\Coupon\Action;

class ByPercent extends AbstractDiscount
{
  /**
   * @param \Vnecoms\VendorsCoupon\Model\Coupon $coupon
   * @param float $maxDiscount
   * @param $address
   */
    public function calculate($coupon, $maxDiscount, $address)
    {
        $rulePercent = min(100, $coupon->getAmount());
        $_rulePct = $rulePercent / 100;
        return $maxDiscount * $_rulePct ;
    }
}
