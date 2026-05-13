/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'ko',
        'Magento_SalesRule/js/view/payment/discount',
        'Magento_Checkout/js/model/quote',
        'Vnecoms_VendorsCoupon/js/action/set-coupon-code',
        'Vnecoms_VendorsCoupon/js/action/cancel-coupon',
        'Vnecoms_VendorsCoupon/js/model/discount',
        'Vnecoms_VendorsCoupon/js/action/get-discount-detail'
    ],
    function ($, ko, Component, quote, setCouponCodeAction, cancelCouponAction, discountModel, getDiscountDetailAction) {
        'use strict';

        var totals = quote.getTotals(),
            couponCode = ko.observable(null),
            isApplied = ko.observable(couponCode() != null);



        return Component.extend({
            defaults: {
                template: 'Vnecoms_VendorsCoupon/payment/discount',
            },
            initialize: function () {
                this._super();
                getDiscountDetailAction();
            },
            couponCode: couponCode,

            /**
             * Applied flag
             */
            isApplied: isApplied,

            getCoupon: function(){
            	var totals = quote.getTotals();
            	return totals()['coupon_code'];
            },
            
            /**
             * Get Discount Detail
             */
            getDiscountDetails: function(){
            	return discountModel.getDiscountDetail();
            },
            
            /**
             * Get Vendor title
             */
            getVendorTitle: function(vendorId){
           		return window.checkoutConfig.coupon_vendor_list['vendor_'+vendorId];
            },
            
            removeDiscount: function(couponData){
            	cancelCouponAction(couponData.label);
            },
            /**
             * Coupon code application procedure
             */
            apply: function() {
                if (this.validate()) {
                    setCouponCodeAction(couponCode());
                    couponCode('');
                }
            },

            /**
             * Cancel using coupon
             */
            cancel: function() {
                cancelCouponAction(this.getCoupon());
            },

            /**
             * Coupon form validation
             *
             * @returns {Boolean}
             */
            validate: function () {
                var form = '#discount-form';

                return $(form).validation() && $(form).validation('isValid');
            }
        });
    }
);
