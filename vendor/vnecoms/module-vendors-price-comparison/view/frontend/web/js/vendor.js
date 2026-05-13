/**
* Copyright © 2016 Magento. All rights reserved.
* See COPYING.txt for license details.
*/
define([
    'uiComponent',
    'jquery',
    'mage/translate',
    'Vnecoms_VendorsPriceComparison/js/action/get-product-comparison',
    'Vnecoms_VendorsPriceComparison/js/model/product-comparison',
    'domReady!'
], function (Component, $, $t, getProductComparisonAction, productComparison) {
    'use strict';

    return Component.extend({
    	 defaults: {
    			template: 'Vnecoms_VendorsPriceComparison/vendor',
    			page_size: 5,
    			is_show_all: false,
    			show_country_filter: true,
          select_from_product_id: 0,
    			products: [],
  			 /*When these values are changed, the template that uses these variables will be changed too*/
          tracks: {
              	is_show_all: true,
          },
          availableCountries: null
    	  },

        initialize: function () {
            this._super()
            .observe({
            	isLoading: false,
            	selectedCountry: '',
              productComparisons: this.products
            });
        },
        /**
         * Is visible
         */
        visible: function(){
        	return this.totalProducts() > 0;
        },
        /**
         * Get total number of products
         */
        totalProducts: function(){
        	return this.productComparisons().length;
        },

        /**
         * [description]
         * @return {[type]} [description]
         */
        totalFilteredProducts: function(){
        	return this.getFilteredProducts().length;
        },

        /**
         * Get filtered products
         */
        getFilteredProducts: function(){
        	var self = this;
        	var result = [];
          var products = this.productComparisons();
          $.each(products, function(index, product){
            if(!self.filterProduct(product)) return true;
            result.push(product);
          });

        	return result;
        },

        /**
         * Get the list of products
         */
        getProducts: function(){
            var self = this;
            var products = this.getFilteredProducts();
            products.sort(function(a,b){
              var totalPriceA = a.total_price != undefined ? a.total_price : a.final_price;
              var totalPriceB = b.total_price != undefined ? b.total_price : b.final_price;

              return parseFloat(totalPriceA) > parseFloat(totalPriceB) ? 1:-1
            });

            if(!this.is_show_all && this.canShowMore()){
              var result = [];
              var count = 0;
              $.each(products, function(index, product){
                if(count >= self.page_size) return false;
                result.push(product);
                count ++;
              });
              return result;
            }

            return products;
        },

        /**
         * Filter product
         */
        filterProduct: function(product){
        	var selectedCountry = this.selectedCountry();
        	var filter1 = (selectedCountry=='' || (product.pc_vendor.country_id == selectedCountry));
        	return filter1;
        },

        /**
         * Get all available countries
         */
        getCountries: function(){
        	if(this.availableCountries === null){
	        	var countries = {};
	        	var countriesArr = [{code: '', country_name: $t('-- Select Country --')}];
	        	$.each(this.productComparisons(),function(index, product){
	        		if(typeof(countries[product.pc_vendor.country_id]) == 'undefined'){
	        			countries[product.pc_vendor.country_id] = product.pc_vendor.pc_country_name;
	        			countriesArr.push({
	        				code: product.pc_vendor.country_id,
	        				country_name: product.pc_vendor.pc_country_name
	    				});
	        		}
	        	});

	        	countriesArr.sort(function(a,b){
	        		return a.code > b.code ? 1:-1
	        	});
	        	this.availableCountries = countriesArr;
        	}

        	return this.availableCountries;
        },

        /**
         * Get country name by code.
         */
        getCountryByCode: function(countryCode){
        	var countries = this.getCountries();
        	var result = countryCode;
        	$.each(countries, function(index, country){
        		if(country.code == countryCode){
        			result = country.country_name;
        			return false;
        		}
        	});
        	return result;
        },

        /**
         * Unselect country
         */
        unselectCountry: function(){
        	this.selectedCountry('');
        },

        /**
         * Unselect country
         */
        setAttributes: function(attributes){
          var self = this;
          this.isLoading(true);
          var deferred = $.Deferred();
          getProductComparisonAction({
            'parentProductId': self.select_from_product_id,
            'attributesConfigurable': attributes
          }, deferred);
          $.when(deferred).done(function () {
              var products = productComparison.getProducts();
              var result = [];
              $.each(products(), function(index, product){
                if(!self.filterProduct(product)) return true;
                result.push(product);
              });
              self.isLoading(false);
              self.productComparisons(result);
          });
        },

        /**
         * Is showing all products
         */
        isShowingAll: function(){
        	return this.is_show_all;
        },

        /**
         * Can show Show More button
         */
        canShowMore: function(){
        	return this.totalFilteredProducts() > this.page_size;
        },

        /**
         * Toggle Show All
         */
        toggleShowAll: function(){
        	this.is_show_all = ! this.is_show_all;
        }
    });
});
