/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'uiComponent',
    'ko',
    'jquery',
    'mage/storage',
    'domReady!'
], function (Component, ko,  $, storage) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Vnecoms_VendorsPriceComparison/vendor/addtocart',
            mainFormProduct: $('#product_addtocart_form')
        },

        getAddToCartUrl: function(product){
            return product.pc_addtocart_url;
        },

        getAddToCartForm: function(product){
            return 'product_addtocart_form_'+product.entity_id;
        },

        submitAddToCart: function(item) {
            if (this.mainFormProduct.validation() && this.mainFormProduct.validation('isValid')) {
                var objectForm = $('#'+ this.getAddToCartForm(item));
                objectForm.submit();
            }
        },

        getElementIdSelectProduct: function(item) {
            return 'product_review_link_'+item.entity_id;
        },

        hasAttributeCustom: function(item) {
            return item.attributesConfigurable != undefined ? true : false;
        },

        viewProduct: function (item) {
            return item.pc_product_url + "?quickview=true";
        }
    });
});
