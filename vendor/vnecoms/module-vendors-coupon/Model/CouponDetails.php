<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Model;

/**
 * @codeCoverageIgnoreStart
 */
class CouponDetails extends \Magento\Framework\DataObject implements \Vnecoms\VendorsCoupon\Api\Data\CouponDetailsInterface
{
    /**
     * @{inheritdoc}
     */
    public function getSuccess()
    {
        return $this->getData(self::SUCCESS);
    }
    
    /**
     * @{inheritdoc}
     */
    public function setSuccess($success)
    {
        return $this->setData(self::SUCCESS, $success);
    }

    /**
     * @{inheritdoc}
     */
    public function getCouponDetail()
    {
        return $this->getData(self::COUPON_DETAIL);
    }

    /**
     * @{inheritdoc}
     */
    public function setCouponDetail($detail)
    {
        return $this->setData(self::COUPON_DETAIL, $detail);
    }


}
