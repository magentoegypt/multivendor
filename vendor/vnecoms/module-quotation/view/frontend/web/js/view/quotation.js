/**
 * Copyright © 2017 Vnecoms, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * For miniQuote
 */
define([
    'jquery',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'ko',
    'underscore',
], function ($, Component, customerData, ko, _) {
    'use strict';

    var sidebarInitialized = false,
        addToQuoteCalls = 0,
        quote;
    return Component.extend({
        quotationPageUrl: window.quotation.quoteUrl,
        quote: {},
        items: ko.observable([]),

        /**
         * @override
         */
        initialize: function () {
            var self = this,
                quoteData = customerData.get('quotation');
            this.update(quoteData());

            /*fix for cached data*/
            customerData.reload(['quotation'], false);
            quoteData.subscribe(function (updatedQuote) {
                addToQuoteCalls--;
                this.isLoading(addToQuoteCalls > 0);
                sidebarInitialized = false;
                this.update(updatedQuote);
            }, this);
            $('[data-block="quote"]').on('contentLoading', function (event) {
                addToQuoteCalls++;
                self.isLoading(true);
            });
            if (quoteData().website_id !== window.quotation.websiteId) {
                customerData.reload(['quotation'], false);
            }

            return this._super();
        },

        isLoading: ko.observable(false),
        initQuoteSidebar: function(){},
        
        /**
         * Close quote sidebar
         */
        closeQuoteSidebar: function(){
        	$('body').click();
        },
        /**
         * Open quote modal
         */
        openQuoteModal: function(){
        	this.closeQuoteSidebar();
        	$('#request-quote-modal').modal('openModal');
        },
        /**
         * Remove quote item
         */
        removeItem: function(itemId){
        	$.ajax({
                url: window.quotation.removeItemUrl,
                data: {item_id: itemId, 'form_key': $.mage.cookies.get('form_key')},
                dataType: 'json',
                type: 'post',                
                success: function (res) {
                	console.log('Remove Item #'+itemId);
                }
            });
        },
        
        /**
         * Update item qty
         */
        updateItemQty: function(item){
        	$.ajax({
                url: window.quotation.updateItemQtyUrl,
                data: {item_id: item.item_id, 'form_key': $.mage.cookies.get('form_key'), item_qty: item.qty()},
                dataType: 'json',
                type: 'post',                
                success: function (res) {
                	console.log('Updated Item #'+item.item_id);
                }
            });
        },
        
        /**
         * Is item qty changed
         */
        isItemQtyChanged: function(item){
        	return item.qty() != item.origin_qty;
        },
        /**
         * @return {Boolean}
         */
        closeSidebar: function () {
            var quote = $('[data-block="quote"]');
            quote.on('click', '[data-action="close"]', function (event) {
                event.stopPropagation();
                quote.find('[data-role="dropdownDialog"]').dropdownDialog('close');
            });

            return true;
        },

        /**
         * @param {String} productType
         * @return {*|String}
         */
        getItemRenderer: function (productType) {
            return this.itemRenderer[productType] || 'defaultRenderer';
        },

        /**
         * Update quote content.
         *
         * @param {Object} updatedQuote
         * @returns void
         */
        update: function (updatedQuote) {
            _.each(updatedQuote, function (value, key) {
                if (!this.quote.hasOwnProperty(key)) {
                    this.quote[key] = ko.observable();
                }
                this.quote[key](value);
            }, this);
            
            this.items([]);
            var items = [];
            _.each(updatedQuote['items'], function(item){
            	item.origin_qty = item.qty;
            	item.qty = ko.observable(item.origin_qty);
            	item.showOptions = ko.observable(false);
            	items.push(item);
            }, this);
            this.items(items);
        },

        /**
         * Get quote param by name.
         * @param {String} name
         * @returns {*}
         */
        getQuoteParam: function (name) {
            if (!_.isUndefined(name)) {
                if (!this.quote.hasOwnProperty(name)) {
                    this.quote[name] = ko.observable();
                }
            }

            return this.quote[name]();
        },
        
        /**
         * Toggle item options
         */
        tonggleItemOption: function(item){
        	item.showOptions(!item.showOptions());
        }
    });
});
