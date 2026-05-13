<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Model\Coupon\Action;

class BuyXGetYPercent extends AbstractDiscount
{
  /**
   * @param \Vnecoms\VendorsCoupon\Model\Coupon $coupon
   * @param float $maxDiscount
   * @param $address
   */
  public function calculate($coupon, $maxDiscount, $address)
  {
      $x = $coupon->getBuyX();
      $rulePercent = min(100, $coupon->getAmount());
      if (!$x || $maxDiscount < $x) {
          return false;
      }
      $rulePercent = min(100, $coupon->getAmount());
      $_rulePct = $rulePercent / 100;
      return $_rulePct * $maxDiscount;
  }
}
