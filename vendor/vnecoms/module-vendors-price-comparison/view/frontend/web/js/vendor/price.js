/**
* Copyright © 2016 Magento. All rights reserved.
* See COPYING.txt for license details.
*/
define([
    'uiComponent',
    'jquery',
    'mage/template',
    'priceUtils',
    'priceBox',
    'domReady!'
], function (Component, $, mageTemplate, utils) {
    'use strict';

    return Component.extend({
    	defaults: {
			template: 'Vnecoms_VendorsPriceComparison/vendor/price',
			priceHolderSelector: '.price-box',
            exchangeRate: 1,
            priceTemplate: '<span class="price"><%- data.formatted %></span>',
            priceFormat: null,
            basePriceFormat: null
    	},
    	
        initialize: function () {
            this._super()
            .observe({
            	isImporting: false,	
            });
        },


        /**
         * Format Price
         * 
         * @param number
         */
        formatPrice: function(number, isBaseCurrency){
            if(typeof(isBaseCurrency) == 'undefined' || !isBaseCurrency){
                number = number * this.exchangeRate; /* Convert to Current currency */
                var priceFormat     = this.priceFormat;
                var priceTemplate   = mageTemplate(this.priceTemplate);
            }else{
                var priceFormat     = this.basePriceFormat;
                var priceTemplate   = mageTemplate(this.priceTemplate);
            }
            
            var priceData = {formatted:utils.formatPrice(number, priceFormat)};
            return priceTemplate({data: priceData});
        },

        /*
        formatPrice: function(number){
        	var priceBoxOption 	= $(this.priceHolderSelector).priceBox('option');
        	var priceFormat 	= priceBoxOption.priceConfig.priceFormat;
        	var priceTemplate 	= mageTemplate(priceBoxOption.priceTemplate);
        	
        	var priceData = {formatted:utils.formatPrice(number, priceFormat)};
            return priceTemplate({data: priceData});
        },
        /**
         * Has special price
         */
        hasSpecialPrice: function(product){
        	return product.price != product.final_price;
        },
        import: function(){
        	
        }
    });
});
