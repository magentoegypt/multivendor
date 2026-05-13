<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Model\Coupon\Action;

/**
 * @api
 * @since 100.0.2
 */
interface DiscountInterface
{
  /**
   * @param \Vnecoms\VendorsCoupon\Model\Coupon $coupon
   * @param float $maxDiscount
   * @param $address
   */
    public function calculate($coupon, $maxDiscount, $address);
}
