/*
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    "jquery",
    'Magento_Ui/js/modal/confirm',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
    'Magento_Sales/order/create/scripts',
    "mage/translate",
    "prototype",
    "Magento_Catalog/catalog/product/composite/configure",
    'Magento_Ui/js/lib/view/utils/async'
], function(jQuery, confirm, alert, $t) {
    'use strict';

    var AdminQuote = Class.create(AdminOrder, {
        initialize : function($super, data){
        	this.currentQuoteId    	= false;
        	this.updateQuoteInfoUrl = '';
        	
            $super(data);

            jQuery.async('#quote-items', (function(){
                this.dataArea = new OrderFormArea('data', $(this.getAreaId('data')), this);
                this.itemsArea = Object.extend(new OrderFormArea('items', $(this.getAreaId('items')), this), {
                    addControlButton: function(button){
                        var controlButtonArea = $(this.node).select('.actions')[0];
                        if (typeof controlButtonArea != 'undefined') {
                            var buttons = controlButtonArea.childElements();
                            for (var i = 0; i < buttons.length; i++) {
                                if (buttons[i].innerHTML.include(button.label)) {
                                    return ;
                                }
                            }
                            button.insertIn(controlButtonArea, 'top');
                        }
                    }
                });

                var searchButton = new ControlButton(jQuery.mage.__('Add Products')),
                    searchAreaId = this.getAreaId('search');
                searchButton.onClick = function() {
                    $(searchAreaId).show();
                    var el = this;
                    window.setTimeout(function () {
                        el.remove();
                    }, 10);
                };

                if (jQuery('#' + this.getAreaId('items')).is(':visible')) {
                    this.dataArea.onLoad = this.dataArea.onLoad.wrap(function(proceed) {
                        proceed();
                        this._parent.itemsArea.setNode($(this._parent.getAreaId('items')));
                        this._parent.itemsArea.onLoad();
                    });

                    this.itemsArea.onLoad = this.itemsArea.onLoad.wrap(function(proceed) {
                        proceed();
                        if ($(searchAreaId) && !$(searchAreaId).visible()) {
                            this.addControlButton(searchButton);
                        }
                    });
                    this.areasLoaded();
                    this.itemsArea.onLoad();
                }
            }).bind(this));
        },

        /**
         * Set Current Quote Id
         */
        setCurrentQuoteId: function(currentQuoteId){
        	this.currentQuoteId = currentQuoteId;
        },
        
        /**
         * Set Update Quote Info URL
         */
        setUpdateQuoteInfoUrl: function(updateQuoteInfoUrl){
        	this.updateQuoteInfoUrl = updateQuoteInfoUrl;
        },
        
        getAreaId : function(area){
            return 'quote-'+area;
        },

        setCustomerId : function(id){
            this.customerId = id;
            this.loadArea('header', true);
            $(this.getAreaId('header')).callback = 'setCustomerAfter';
            $('back_quote_top_button').hide();
            $('reset_quote_top_button').show();
        },

        setStoreId : function(id){
            this.storeId = id;
            this.storeSelectorHide();
            this.dataShow();
            this.loadArea(['header', 'data'], true);
        },

        prepareParams : function(params){
            if (!params) {
                params = {};
            }
            if (!params.customer_id) {
                params.customer_id = this.customerId;
            }
            if (!params.store_id) {
                params.store_id = this.storeId;
            }
            if (!params.currency_id) {
                params.currency_id = this.currencyId;
            }
            if (!params.form_key) {
                params.form_key = FORM_KEY;
            }

            return params;
        },

        areaOverlay : function()
        {
            $H(quote.overlayData).each(function(e){
                e.value.fx();
            });
        },

        dataShow : function() {
            if ($('submit_quote_top_button')) {
                $('submit_quote_top_button').show();
            }
            this.showArea('data');
        },

        /**
         * Submit configured products to quote
         */
        productGridAddSelected : function(){
            if(this.productGridShowButton) Element.show(this.productGridShowButton);
            var area = ['search', 'items'];
            /* prepare additional fields and filtered items of products */
            var fieldsPrepare = {};
            var itemsFilter = [];
            var products = this.gridProducts.toObject();
            for (var productId in products) {
                itemsFilter.push(productId);
                var paramKey = 'item['+productId+']';
                for (var productParamKey in products[productId]) {
                    paramKey += '['+productParamKey+']';
                    fieldsPrepare[paramKey] = products[productId][productParamKey];
                }
            }
            fieldsPrepare['quote_id'] = this.currentQuoteId;
            
            this.productConfigureSubmit('product_to_add', area, fieldsPrepare, itemsFilter);
            productConfigure.clean('quote_items');
            this.hideArea('search');
            this.gridProducts = $H({});
        },

        removeQuoteItem : function(id){
            this.loadArea(['items'], true,
                {remove_item:id, quote_id: this.currentQuoteId});
        },
        
        /**
         * Change Quote Status
         */
        changeQuoteInfo: function(fieldName, fieldValue){
        	var data = {
    			quote_id: this.currentQuoteId,
    			field: fieldName,
			};
        	data[fieldName] = fieldValue;
        	
        	this._callAjax(
    			this.updateQuoteInfoUrl,
    			data, 
    			function(response){
    				if(response.error){
    					alert({
    						title: $t('Error'),
    	                    content: response.message
    	                });
    				}
    			}
			);
        },
        
        send: function () {
            var disableAndSave = function() {
                disableElements('save');
                jQuery('#edit_form').on('invalid-form.validate', function() {
                    enableElements('save');
                    jQuery('#edit_form').trigger('processStop');
                    jQuery('#edit_form').off('invalid-form.validate');
                });
                jQuery('#edit_form').triggerHandler('save');
            }
            if (this.orderItemChanged) {
                var self = this;

                jQuery('#edit_form').trigger('processStop');

                confirm({
                    content: jQuery.mage.__('You have item changes'),
                    actions: {
                        confirm: function() {
                            jQuery('#edit_form').trigger('processStart');
                            disableAndSave();
                        },
                        cancel: function() {
                            self.itemsUpdate();
                        }
                    }
                });
            } else {
                disableAndSave();
            }
        },

        loadAreaResponseHandler : function (response) {
            if (response.error) {
                alert({
                    content: response.message
                });
            }
            if (response.ajaxExpired && response.ajaxRedirect) {
                setLocation(response.ajaxRedirect);
            }
            if (!this.loadingAreas) {
                this.loadingAreas = [];
            }
            if (typeof this.loadingAreas == 'string') {
                this.loadingAreas = [this.loadingAreas];
            }
            if (this.loadingAreas.indexOf('message') == -1) {
                this.loadingAreas.push('message');
            }
            if (response.header) {
                jQuery('.page-actions-inner').attr('data-title', response.header);
            }

            for (var i = 0; i < this.loadingAreas.length; i++) {
                var id = this.loadingAreas[i];
                if ($(this.getAreaId(id))) {
                    if ('message' != id || response[id]) {
                        $(this.getAreaId(id)).update(response[id]);
                    }
                    if ($(this.getAreaId(id)).callback) {
                        this[$(this.getAreaId(id)).callback]();
                    }
                }
            }
            jQuery('.quote-item-proposals-container').applyBindings();
        },

        /**
         * Call AJAX
         * @param url
         * @param params
         * @private
         */
        _callAjax: function (url, params, callBack) {
        	jQuery.ajax({
                url: url,
                method: "POST",
                data: params,
                showLoader: true,
                dataType: "json"
            }).done(function (response) {
            	if(callBack) callBack(response);
            }).fail(function () {
            	alert({
            		title: $t('Error'),
                    content: $t('Something wrong. Please try to refresh the page.')
                });
            });
        },
        
        /**
         * toggle product remark per row
         * @param checkbox
         * @param elemId
         * @param tierBlock
         */
        toggleProductRemark: function(checkbox, elemId, tierBlock) {
            if (checkbox.checked) {
                $(elemId).disabled = false;
                $(elemId).show();
                if($(tierBlock)) $(tierBlock).hide();
            }
            else {
                $(elemId).disabled = true;
                $(elemId).hide();
                if($(tierBlock)) $(tierBlock).show();
            }
        },

        hold: function () {
            var url = $('#quote-view-unhold-button').data('url');

            getForm(url).appendTo('body').submit();
        },

        unHold: function () {
            var url = $('#quote-view-unhold-button').data('url');

            getForm(url).appendTo('body').submit();
        }
    });

    window.AdminQuote = AdminQuote;
});
/* jshint ignore:end */
