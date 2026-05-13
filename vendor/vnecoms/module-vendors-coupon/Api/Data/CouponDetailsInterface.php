<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Api\Data;

/**
 * Interface PaymentDetailsInterface
 * @api
 */
interface CouponDetailsInterface
{
    /**#@+
     * Constants defined for keys of array, makes typos less likely
     */
    const SUCCESS = 'success';

    const COUPON_DETAIL = 'coupon_detail';

    /**#@-*/

    /**
     * @return boolean
     */
    public function getSuccess();

    /**
     * @param boolean $success
     * @return $this
     */
    public function setSuccess($success);

    /**
     * @return string
     */
    public function getCouponDetail();

    /**
     * @param string $detail
     * @return $this
     */
    public function setCouponDetail($detail);

}
