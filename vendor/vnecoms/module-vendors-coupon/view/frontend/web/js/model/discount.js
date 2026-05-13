/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
     	'ko',
     	'jquery',
        'mageUtils'
    ],
    function(ko, $, utils) {
        "use strict";
        var discountDetail = ko.observable(null);
        
        var discountData = window.checkoutConfig.quoteData.vendor_discount_detail;
        if(discountData && discountData.length){
        	discountDetail($.parseJSON(discountData));
        }
        
        return {
        	getDiscountDetail(){
        		return discountDetail();
        	},
        	
        	setDiscountDetail(data){
        		discountDetail(data)
        	}
        };
    }
);
