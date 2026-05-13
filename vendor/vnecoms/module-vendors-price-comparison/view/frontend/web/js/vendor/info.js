/**
* Copyright © 2016 Magento. All rights reserved.
* See COPYING.txt for license details.
*/
define([
    'uiComponent',
    'jquery',
    'mage/translate',
    'domReady!'
], function (Component, $, $t) {
    'use strict';

    return Component.extend({
    	defaults: {
			template: 'Vnecoms_VendorsPriceComparison/vendor/info',
			show_address: true,
			show_sales_count: true,
			show_joined_date: true
    	},
    	
        initialize: function () {
            this._super();
        },
        /**
         * Get current vendor
         * This value is set from template vendor.html
         * 
         * @param record
         * @return vendor 	{entity_id: 1, vendor_id: 'vendor01', title: 'Vendor Store' ...}
         */
        getVendor: function(record){
        	return record.pc_vendor;
        },
        /**
         * Get vendor title
         * 
         * @param record
         * @return string
         */
        getVendorTitle: function(record){
        	return this.getVendor(record).pc_title?this.getVendor(record).pc_title:this.getVendor(record).vendor_id;
        },
        /**
         * Get vendor description
         * 
         * @param record
         * @return string
         */
        getVendorDescription: function(record){
        	return this.getVendor(record).pc_description;
        },
        /**
         * Get vendor logo URL
         * 
         * @param record
         * @return string
         */
        getLogoUrl(record){
        	return this.getVendor(record).pc_logo_url;
        },
        /**
         * Get vendor logo width
         * 
         * @param record
         * @return int
         */
        getLogoWidth(record){
        	return this.getVendor(record).pc_logo_width;
        },
        /**
         * Get vendor logo height
         * 
         * @param record
         * @return int
         */
        getLogoHeight(record){
        	return this.getVendor(record).pc_logo_height;
        },
        /**
         * Get vendor home page URL
         * 
         * @param record
         * @return string
         */
        getHomepageUrl: function(record){
        	return this.getVendor(record).pc_home_page;
        },
        /**
         * Get vendor address
         * 
         * @param record
         * @return string
         */
        getAddress: function(record){
        	return this.getVendor(record).pc_address;
        },
        /**
         * Get vendor sales count
         * 
         * @param record
         * @return string
         */
        getSalesCount: function(record){
        	return this.getVendor(record).pc_sales_count;
        },
        /**
         * Get vendor sales count
         * 
         * @param record
         * @return string
         */
        getJoinedDate: function(record){
        	return this.getVendor(record).pc_joined_date;
        },
        canShowAddress: function(){
        	return this.show_address;
        },
        canShowSalesCount: function(){
        	return this.show_sales_count;
        },
        canShowJoinedDate: function(){
        	return this.show_joined_date;
        }
    });
});
