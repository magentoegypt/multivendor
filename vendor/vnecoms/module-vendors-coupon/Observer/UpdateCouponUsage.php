<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Observer;

use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsConfig\Helper\Data;

class UpdateCouponUsage implements ObserverInterface
{
    /**
     * @var \Vnecoms\VendorsCoupon\Model\CouponFactory
     */
    protected $_couponFactory;
    
    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;

    /**
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory
     */
    public function __construct(
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Vnecoms\VendorsCoupon\Model\CouponFactory $couponFactory
    ){
        $this->_vendorHelper = $vendorHelper;
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
        /* Do nothing if the extension is not enabled.*/
        if(!$this->_vendorHelper->moduleEnabled()) return;
        
        $order = $observer->getOrder();
        $quote = $observer->getQuote();
        
        $appliedCoupons = $quote->getData('applied_vendor_coupon_ids');
        if(!$appliedCoupons) return;
        
        $appliedCoupons = explode(",", $appliedCoupons);
        foreach($appliedCoupons as $couponId){
            $coupon = $this->_couponFactory->create()->load($couponId);
            if(!$coupon->getId()) continue;
            
            $coupon->setTimesUsed($coupon->getTimesUsed() + 1)->save();
        }
        return $this;
    }
}
