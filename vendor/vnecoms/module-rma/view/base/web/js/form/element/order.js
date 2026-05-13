/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'uiRegistry',
    'jquery',
    'Magento_Ui/js/form/element/abstract',
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
            elementTmpl: 'Vnecoms_RMA/form/element/order',
            exports: {
                items: '${ $.provider }:order_item_id.value'
            }
        },

        /**
         * Initializes component, invokes initialize method of Abstract class.
         *
         * @returns {Object} Chainable.
         */
        initialize: function () {
            this._super()
                .setInitialValue()
                ._setClasses()
                .initSwitcher();
            this.userChanges();
            return this;
        },

        userChanges : function () {
            var _this = this;
            clearTimeout(_this.orderClock);
            _this.orderClock = setTimeout(function () {
                _this._ajaxLoadOrder();
            }, 2000);
        },

        disableButtonSave : function (chPassed,vnecomsRmaFormAdvice) {
            if (chPassed) {
                $('save').removeClassName('disabled');
                $('save').removeAttribute('disabled');
                $('save').setAttribute('aria-disabled',false);
                this.setItemData();
            } else {
                $('save').addClassName('disabled');
                $('save').setAttribute('disabled','disabled');
                $('save').setAttribute('aria-disabled',true);
            }
            return true;
        },

        setItemData : function () {
            var itemsData = {
                accounting: []
            };
            $$('#ves-list-product-order .ves-items-count').each(function (element) {
                if (element.value > 0 && !element.disabled) {
                    itemsData.accounting.push({
                        "item_id" : element.name,
                        "item_qty"  : element.value,
                    });
                }
            });
            this.set("items",itemsData.accounting);
        },

        checkItemSelect : function (formControl,vnecomsRmaFormAdvice) {
            var check = false;
            $$('#ves-list-product-order .ves-items-count').each(function (element) {
                if (formControl.validateItemCount('change', element)
                    && formControl.validateItemCountMax('change', element)) {
                    check = true;
                }
            });

            $$('#ves-list-product-order .ves-items-count').each(function (element) {
                if (!formControl.validateItemCountMax('change', element)) {
                    check = false;
                }
            });
            return check;
        },

        _ajaxLoadOrder : function () {
            var _this = this;
            var text = $$('[name="order_incremental_id"]').first().value;
            if (!text) {
return _this; }
          //  var text = this.value();
            new Ajax.Request(this.ajax_url, {
                method:'post',
                parameters:{increment_id:text},
                onSuccess: function (transport) {
                    var response = transport;
                    if (response.responseText == "false") {
                        alert(_this.note);
                        _this.value("");
                    } else {
                        $('ves-list-product-order').update(response.responseText);
                        $('ves-list-product-order').show();
                        _this.setItemData();
                        var vnecomsRmaFormAdvice = new VnecomsRmaFormAdvice();
                        var formControl = new VnecomsRmaOrdersControl(null, null);
                        formControl.observeItemsCount();
                        $$('#ves-list-product-order .ves-items-count').each(function (element) {
                            Event.observe(element, 'change', function () {
                                var checkItemSelect =  _this.checkItemSelect(formControl,vnecomsRmaFormAdvice);
                                if (!checkItemSelect) {
                                    _this.disableButtonSave(false,vnecomsRmaFormAdvice);
                                    if (!formControl.validateItemCount('change', element)
                                        || !formControl.validateItemCountMax('change', element)
                                    ) {
                                        _this.disableButtonSave(false,vnecomsRmaFormAdvice);
                                    } else {
                                        _this.disableButtonSave(true,vnecomsRmaFormAdvice);
                                    }
                                } else {
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