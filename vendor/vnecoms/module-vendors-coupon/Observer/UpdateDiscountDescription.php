<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsConfig\Helper\Data;

class UpdateDiscountDescription implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsCoupon\Model\CouponFactory
     */
    protected $_couponFactory;

    /**
     * @param \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory
     */
    public function __construct(
        \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory
    ){
        $this->_couponFactory = $couponFactory;
    }
    
    /**
     * Add multiple vendor order row for each vendor.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return self
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
                
        $orderDataObj = $observer->getOrderData();
        $quote = $observer->getQuote();
        $vendorId = $observer->getVendorId();
        
        $appliedCoupons = $quote->getData('applied_vendor_coupon_ids');
        if(!$appliedCoupons) return;
        
        $appliedCoupons = explode(",", $appliedCoupons);
        foreach($appliedCoupons as $couponId){
            $coupon = $this->_couponFactory->create()->load($couponId);
            if(!$coupon->getId() || $coupon->getVendorId() != $vendorId) continue;
            
            $orderDataObj->setCouponCode($coupon->getCode());
            $orderDataObj->setDiscountDescription($coupon->getCode());
        }
        return $this;
    }
}
