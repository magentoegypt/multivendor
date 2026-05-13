/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'uiRegistry',
    'jquery',
    'Vnecoms_RMA/js/form/element/order',
    'Vnecoms_RMA/js/rma'
], function (_,registry, Jquery , Acstract) {
    'use strict';
    return Acstract.extend({
        defaults: {
            visible: false,
            labelVisible: true,
            required: true,
            orderClock: null,
            items : {},
            amount : null,
            elementTmpl: 'Vnecoms_RMA/form/element/order',
            exports: {
                items: '${ $.provider }:order_item_id.value',
                amount: '${ $.provider }:order_item_id.amount'
            }
        },

        /* returns self object name */
        setTotalMaxRefundAmount: function(obj) {
            var totalValidateAmount = 0;
            $$(".ves-items-count").each(function(obj) {
                var price = $("orderitem-amount"+obj.name).value;
                var itemQty = $("orderitem-count"+obj.name).value;
                price = parseFloat(price);
                if (obj && !obj.disabled) {
                    totalValidateAmount += price*itemQty;
                }
            });
            this.set("amount",totalValidateAmount);
            return this;
        },

        _ajaxLoadOrder : function(){
            var _this = this;
            var text = $$('[name="order_incremental_id"]').first().value;
            if(!text) return _this;
            new Ajax.Request(this.ajax_url, {
                method:'post',
                parameters:{increment_id:text},
                onSuccess: function(transport) {
                    var response = transport;
                    if(response.responseText == "false") {
                        alert(_this.note);
                        _this.value("");
                    }
                    else{
                        $('ves-list-product-order').update(response.responseText);
                        $('ves-list-product-order').show();
                        _this.setItemData();
                        var vnecomsRmaFormAdvice = new VnecomsRmaFormAdvice();
                        var formControl = new VnecomsRmaOrdersControl(null, null);
                        formControl.observeItemsCount();
                        $$('#ves-list-product-order .ves-items-count').each(function(element) {
                            Event.observe(element, 'change', function() {
                                _this.setTotalMaxRefundAmount();

                                var checkItemSelect =  _this.checkItemSelect(formControl,vnecomsRmaFormAdvice);
                                if(!checkItemSelect){
                                    _this.disableButtonSave(false,vnecomsRmaFormAdvice);
                                    if(!formControl.validateItemCount('change', element)
                                        || !formControl.validateItemCountMax('change', element)
                                    ){
                                        _this.disableButtonSave(false,vnecomsRmaFormAdvice);
                                    }else{
                                        _this.disableButtonSave(true,vnecomsRmaFormAdvice);
                                    }
                                }else{
                                    _this.disableButtonSave(true,vnecomsRmaFormAdvice);
                                }

                            });
                        }, this);

                    }
                },
            });


        }
    });
});