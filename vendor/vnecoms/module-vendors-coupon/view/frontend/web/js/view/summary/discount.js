/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*global define*/
define(
    [
        'Magento_SalesRule/js/view/summary/discount',
        'jquery',
        'Vnecoms_VendorsCoupon/js/model/discount'
    ],
    function (Component, $, discountModel) {
        "use strict";
        return Component.extend({
            /**
             * Get Discount Detail
             */
            getDiscountDetails: function(){
            	return discountModel.getDiscountDetail();
            },
            isLastDiscountDetail: function(index){
            	return index() == (this.getDiscountDetails().length - 1);
            },
            canShowDetail: function(){
            	return window.checkoutConfig.coupon_show_detail;
            },
            canShowStoreName: function(){
            	return window.checkoutConfig.coupon_show_seller_store;
            },
            getVendorTitle: function(vendorId){
            	 return window.checkoutConfig.coupon_vendor_list['vendor_'+vendorId];
            }

        });
    }
);
