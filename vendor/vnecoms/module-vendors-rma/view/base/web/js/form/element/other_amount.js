/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract',
    'Magento_Ui/js/lib/validation/validator'
], function ($,_,registry, Acstract , validator) {
    'use strict';


    return Acstract.extend({
        defaults: {
            visible: false,
            labelVisible: true,
            required: true,
            imports: {
                showOtherAmountField: '${ $.parentName }.refund_amount_type:value',
                showRefundField: '${ $.parentName }.type:value',
                setAmountFieldValue: '${ $.parentName }.order_incremental_id:amount'
            }
        },

        /**
         * Initializes component, invokes initialize method of Abstract class.
         *
         *  @returns {Object} Chainable.
         */
        initialize: function () {
            this._super()
                .setInitialValue()
                ._setClasses()
                .initSwitcher();
            validator.addRule(
                "validate-custom-amount",
                function(value, params) {
                    var check = true;
                    var totalValidateAmount = 0 ;

                    $(".ves-items-count").each(function(i, obj) {
                        var price = $("#orderitem-amount"+obj.name).val();
                        var itemQty = $( this ).val();

                        if (obj && !obj.disabled) {
                            var tmpTotal = parseFloat(price) * parseInt(itemQty);
                            totalValidateAmount += parseFloat(tmpTotal.toFixed(2));
                        }
                    });
                    if(value > totalValidateAmount) check = false;
                    return check;
                },
                $.mage.__('Amount is not valid .')
            );

            return this;
        },

        showRefundField : function(value){
            this.visible(value == "refund" ? true : false);
        },

        showOtherAmountField : function(value){
            this.visible(value == "custom_amount" ? true : false);
        },

        setAmountFieldValue : function(amount){
            this.value(amount);
        }
    });

});