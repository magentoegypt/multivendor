/**
 * Copyright © 2017 Vnecoms, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true, jquery:true*/
define([
    "jquery",
    "uiRegistry",
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'mage/mage',
    'Magento_Catalog/js/catalog-add-to-cart'
], function($, registry, $t, alert){
    "use strict";

    $.widget('ves.quoteProductsList', {
        options: {
        	containerSelector: '',
            addToCartBtnSelector: '[data-role="tocart-form"] .tocart',
            productData: []
        },

        _create: function() {
        	var self = this;
        	this._updateProductData();
        	if(!$(this.options.containerSelector).length) return;

        	this._updateQuoteButtons();
        	this._bindButtonEvents();
        	$('body').addClass('ves-quotation-processed');
        },

        /**
         * Update product Data
         */
        _updateProductData: function(){
        	var productData = this.options.productData;
        	if(!window.quotationConfig){
        		window.quotationConfig = productData;
        	}else{
        		for(var index in productData){
        			if(typeof(window.quotationConfig[index]) == 'undefined'){
        				window.quotationConfig[index] = productData[index];
        			}
        		}
        	}
        },

        /**
         * Update quote button
         */
        _updateQuoteButtons: function(){
        	var self = this;
        	$(this.options.addToCartBtnSelector).each(function(){
        		var form = $(this).closest('.product').find('form[data-role=tocart-form]').first();
        		if(!form || form.hasClass('updated-quote-btn')) return true; /*Make sure the quote btn is not added multiple times*/
        		form.addClass('updated-quote-btn');

        		var productId = form.find('[name="product"]').first().val();
        		if(!productId) return true;

        		var quoteConfig = window.quotationConfig['product_'+productId];
        		if(!quoteConfig) return true;

        		if(!quoteConfig['order_mode']){
                    form.find('.tocart').attr('disabled','disabled');
                }

        		if(quoteConfig['quote_mode']){
                    if(!quoteConfig['order_mode']){
                        var addToCartBtn = form.find('.tocart');
                        addToCartBtn.after('<button type="button" title="'+$t('Add to Quote')+'" class="action tocart primary category-btn-addtoquote product-addtoquote-button"><span>'+$t('Add to Quote')+'</span></button>');
                        addToCartBtn.remove();
                    }else{
                    	$(this).prepend('<a href="javascript: void(0)" title="'+$t('Add to Quote')+'" class="action category-link-addtoquote product-addtoquote-link quote-icon-file-text"><span>'+$t('Add to Quote')+'</span></a>');
                    }
                }
        	});
        },
        /**
         * Bind button events
         */
        _bindButtonEvents: function(){
        	var self = this;
        	$(".product-addtoquote-button, .product-addtoquote-link").each(function() {
        		if($(this).hasClass('quote-binded-event')) return true;
                $(this).on( "click",function (event) {
                    if($(this).hasClass('disabled')) return;
                    self.submitQuoteForm($(this));
                    event.preventDefault();
                });
                $(this).addClass('quote-binded-event');
            });
        },

        /**
         * Submit quote form
         */
        submitQuoteForm: function (button){
        	var form = button.closest('.product').find('form[data-role=tocart-form]').first();
        	form.validation();
            if(!form.validation('isValid')) return;

            var quotation = registry.get('quote_content');
            if(quotation){
        	    quotation.isLoading(true);
            }

        	if(button.hasClass('product-addtoquote-button')){
        		button.attr('disabled','disabled');
        		button.find('span').html($t("Adding ..."));
        	}else if(button.hasClass('product-addtoquote-link')){
            	button.addClass('disabled');
        	}
            var formData = {};
            var inputs = form.serializeArray();
            $.each(inputs, function (i, input) {
            	formData[input.name] = input.value;
            });

            var action = form.attr('action').replace("checkout/cart/add", "quotation/quote/add");
            $.ajax({
                url: action,
                method: "POST",
                data: formData,
                dataType: "json"
            }).done(function( response ){
            	if(response.backUrl){
            		window.location = response.backUrl;
            	}else if(response.error){
                	alert({
            			title: $t('Error'),
            			content: response.message,
            		});
                }else{
                	button.find('span').html($t('Added'));
                }
                setTimeout(function(){
            		button.find('span').html($t("Add to Quote"));
            		button.removeAttr('disabled');
            		button.removeClass('disabled');
            	}, 1000);
            });
        }

    });

    return $.ves.quoteProductsList;
});
