/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'uiRegistry',
    "mage/adminhtml/wysiwyg/tiny_mce/setup",
    "mage/adminhtml/wysiwyg/widget",
    "prototype"
], function (Jquery,modal,registry,tinyMCE) {
    'use strict';

    var VnecomsRmaOrdersControl = Class.create({
        /**
         * Class initialization method. Sets default values
         * for all class variables. Url for AJAX Request can
         * be provided on class creating
         */
        initialize: function (globalObject, awrmaNewForm) {
            /* Id of select with orders */
            this.orderSelectId = 'order_incremental_id';
            /* Id of order items container */
            this.orderItemsContainer = 'ves-list-product-order';

            /* Id of tr with no order message */
            this.orderItemsNoOrder = 'ves-items-noorder';
            /*Id of tr with no items message */
            this.orderItemsNoItems = 'ves-items-noitems';
            /* Id of tr with error message */
            this.orderItemsError = 'ves-items-error';

            /* Id of service rows container */
            this.orderItemsHeadContainer = 'ves-items-head';
            /* Class of inputs with items count */
            this.itemsCountClass = '.ves-items-count';
            /* Class of checboxes that selects items */
            this.itemsSelectedClass = '.request-items-selected';
            /* Id of select/unselect all items toggle */
            this.itemsSelectToggle = 'ves-items-select-toggle';
            /* Order item row id */
            this.orderItemRow = 'order-item-row-';
            /* Form id */
            this.formId = 'request-new-form';
            /* Id of count field */
            this.orderItemCount = 'orderitem-count';
            /* Id of submit button */
            this.submitId = 'request-new-submit';
            /* Id of ordered items table */
            this.orderedItemsTableId = 'my-request-table';

            /* No remove class for synchronization */
            this.noRemoveClass = 'ves-js-noremove';

            /* Varien Form object */
            this.form = typeof(awrmaNewForm) == 'undefined' ? null : awrmaNewForm;
            /* sync items */
            this.syncOrderItems = null;
            /* Registering global object */
            this.global = typeof(globalObject) == 'undefined' ? window : globalObject;


            /* Initial actions */
            if ($(this.formId)) {
                $(this.formId).onsubmit = this.validateForm.bind(this);
            }
        },

        /* moves all service rows to head container */
        hideServiceRows: function () {
            $(this.orderItemsHeadContainer).appendChild($(this.orderItemsNoOrder).hide());
            $(this.orderItemsHeadContainer).appendChild($(this.orderItemsNoItems).hide());
            $(this.orderItemsHeadContainer).appendChild(loader.close());
            $(this.orderItemsHeadContainer).appendChild($(this.orderItemsError).hide());
            this.disableSelectAllToggle();
        },

        /* shows row with "no order selected" message */
        showNoOrderRow: function () {
            this.hideServiceRows();
            $(this.orderItemsContainer).update('');
            $(this.orderItemsContainer).appendChild($(this.orderItemsNoOrder).show());
            decorateTable(this.orderedItemsTableId);
        },

        /* shows row with "no items selected" message */
        showNoItemsRow: function () {
            this.hideServiceRows();
            $(this.orderItemsContainer).update('');
            $(this.orderItemsContainer).appendChild($(this.orderItemsNoItems).show());
            decorateTable(this.orderedItemsTableId);
        },

        /* remove item row */
        removeItem: function (itemId) {
            var classes = itemId.split(' ');
            $(classes[0]).remove();
            if (classes.length > 1) {
                if ($$('.' + classes[2]).length < 1) {
                    $(classes[2].slice(7)).remove();
                    if ($(classes[2].slice(7) + '-options') != null) {
                        $(classes[2].slice(7) + '-options').remove();
                    }
                }
                if ($$('.' + classes[1]).length < 1) {
                    $(classes[1].slice(7)).remove();
                }
            }
            if ($(this.orderItemsContainer).empty()) {
                this.showNoItemsRow(); }
            decorateTable(this.orderedItemsTableId);
        },

        /* shows row with ajax loader */
        showLoadingLine: function () {
            this.hideServiceRows();
            $(this.orderItemsContainer).update('');
            $(this.orderItemsContainer).appendChild(loader.show());
            decorateTable(this.orderedItemsTableId);
        },

        /* shows row with ajax error message */
        showAjaxError: function () {
            this.hideServiceRows();
            $(this.orderItemsContainer).update('');
            $(this.orderItemsContainer).appendChild($(this.orderItemsError).show());
            $(this.orderSelectId).enable();
            decorateTable(this.orderedItemsTableId);
        },

        /* observer to order select */
        orderChanged: function () {
            var orderIncrementId = $(this.orderSelectId).value;
            if (orderIncrementId == '') {
                this.showNoOrderRow();
            } else {
                this.loadItemsForOrder(orderIncrementId);
            }
        },

        /* Validates all items and call standart validate if no errors */
        validateForm: function () {
            if ($(this.submitId)) {
            $(this.submitId).addClassName('disabled').writeAttribute('disabled', 'disabled'); }
            var chPassed = false;

            var selectItems = $$('#'+this.orderItemsContainer+' '+this.itemsSelectedClass);
            if (selectItems.length > 0) {
                selectItems.each(function (element) {
                    if (this.validateItemsSelected(element)) {
                        chPassed = true;
                    }
                }, this);

                if (!chPassed) {
                    vnecomsRmaFormAdvice.showAdvice($(this.orderedItemsTableId), 'Please select items');
                } else {
                    vnecomsRmaFormAdvice.removeAdvice($(this.orderedItemsTableId));
                }
            } else {
                chPassed = true;
            }

            $$('#'+this.orderItemsContainer+' '+this.itemsCountClass).each(function (element) {
                if (!this.validateItemCount('change', element)) {
                    chPassed = false; }
            }, this);

            if (tinyMCE.get('content_message_reply')) {
                var content = tinyMCE.get('content_message_reply').getContent();
                if (!content) {
                    vnecomsRmaFormAdvice.showAdvice($("content_message_reply_parent"), 'This is a required field.');
                    chPassed = false;
                } else {
                    vnecomsRmaFormAdvice.removeAdvice($("content_message_reply_parent"));
                }
            }

            /* if(!this.form || !this.form.validator || !this.form.validator.validate()) {
             $(this.submitId).removeClassName('disabled').writeAttribute('disabled', null);
             return false;
             }
             */
            if ($(this.submitId)) {
                if (chPassed) {
                    return this.form.submit(); } else {
                    if ($(this.submitId)) {
                     $(this.submitId).removeClassName('disabled').writeAttribute('disabled', null);
                    }
                    return false;
                }
            }
        },

        validateItemCountMax: function (event, element) {

            if (typeof(element) == 'undefined') {
                element = this;
            }
            var maxCount = $(element.identify()+'-maxcount').value;
            var value = parseInt(element.value);

            if (element && element.disabled) {
                return vnecomsRmaFormAdvice.removeAdvice($(element).identify());
            }

            if (!isNaN(value)) {
                if (value > maxCount) {
                    return vnecomsRmaFormAdvice.showAdvice($(element).identify(), 'Wrong quantity');
                } else {
                    element.value = value;
                }
            } else {
                return vnecomsRmaFormAdvice.showAdvice($(element).identify(), 'Not a number');
            }
            return vnecomsRmaFormAdvice.removeAdvice($(element).identify());
        },

        validateItemCount: function (event, element) {
            if (typeof(element) == 'undefined') {
                element = this;
            }
            var selectItem = $(element.getAttribute('selectitemid'));
            if (selectItem && !selectItem.checked) {
                return vnecomsRmaFormAdvice.removeAdvice($(element).identify());
            }

            if (selectItem && selectItem.disabled) {
                return vnecomsRmaFormAdvice.removeAdvice($(element).identify());
            }

            var maxCount = $(element.identify()+'-maxcount').value;
            var value = parseInt(element.value);

            if (value != '') {
                if (!isNaN(value)) {
                    if (value < 1) {
                        return vnecomsRmaFormAdvice.showAdvice($(element).identify(), 'Wrong quantity');
                    } else {
                        element.value = value;
                    }
                } else {
                    return vnecomsRmaFormAdvice.showAdvice($(element).identify(), 'Not a number');
                }
            } else {
                return vnecomsRmaFormAdvice.showAdvice($(element).identify(), 'Can\'t be empty');
            }

            return vnecomsRmaFormAdvice.removeAdvice($(element).identify());
        },

        validateItemsSelected: function (element) {
            return element.checked && !element.disabled;
        },

        /* returns self object name */
        getSelfObjectName: function () {
            for (var name in this.global) {
                if (this.global[name] == this) {
                    return name; }
            }

            return false;
        },

        /* synchronize items in form with items in session */
        syncItems: function (items) {
            if (this.syncOrderItems == null && typeof(items) != 'undefined') {
                this.syncOrderItems = items;
            } else if (this.syncOrderItems) {
                for (var key in this.syncOrderItems) {
                    if ($(this.orderItemRow+key)) {
                        $(this.orderItemRow+key).addClassName(this.noRemoveClass);
                        $(this.orderItemCount+key).value = this.syncOrderItems[key];
                    } }
                $$('#'+this.orderItemsContainer+'>*').each(function (element) {
                    if (element.hasClassName(this.noRemoveClass)) {
                        element.removeClassName(this.noRemoveClass);
                    }
                    else {
                        element.remove();
                    }
                }, this);
                this.syncOrderItems = null;
            }
        },

        /* add onchange handler to validate items count */
        observeItemsCount: function () {
            if (this.getSelfObjectName()) {
                $$(this.itemsCountClass).each(function (obj) {
                    obj.observe('change', this.global[this.getSelfObjectName()].validateItemCount);
                }, this);
            }
        },

        /* add onclick handler to select/unselect all checkbox */
        observeSelectAllToggle: function () {
            var me = this;
            var toggle = $(this.itemsSelectToggle);
            if (toggle) {
                this.enableSelectAllToggle();
                toggle.observe('click', function (event) {
                    $$('#'+me.orderItemsContainer+' '+me.itemsSelectedClass).each(function (element) {
                        element.checked = this.checked;
                    }, this);
                });
            }
        },

        disableSelectAllToggle: function () {
            if ($(this.itemsSelectToggle)) {
                $(this.itemsSelectToggle).writeAttribute('disabled').checked = false;
            }
        },

        enableSelectAllToggle: function () {
            if ($(this.itemsSelectToggle)) {
                $(this.itemsSelectToggle).writeAttribute('disabled', null).checked = false;
            }
        }
    });
    window.VnecomsRmaOrdersControl = VnecomsRmaOrdersControl;
    /**
     * Comment form control
     */
    var VnecomsRmaCommentFormControl = Class.create({
        initialize: function (vf) {
            /* Varien Form */
            this.form = vf;
            /* Comment form id */
            this.commentFormId = 'vnecomsrma-comment-form';
            /* Comment form submit id */
            this.submitComment = 'vnecomsrma-comment-submit';

            $(this.commentFormId).onsubmit = this.validateForm.bind(this);
        },

        validateForm: function () {
            $(this.submitComment).addClassName('disabled').writeAttribute('disabled', 'disabled');

            if (!this.form || !this.form.validator || !this.form.validator.validate()) {
                $(this.submitComment).removeClassName('disabled').writeAttribute('disabled', null);
                return false;
            }

            return this.form.submit();
        }
    });
    window.VnecomsRmaCommentFormControl = VnecomsRmaCommentFormControl;
    /**
     * Admin RMA Form control
     */
    var VnecomsRmaAdminRmaFormControl = Class.create({
        initialize: function (globalObject, vf) {
            /* Varien Form object */
            this.form = vf;
            /* Form Id */
            this.formId = vf.formId;
            /* Class of inputs with items count */
            this.itemsCountClass = '.ves-items-count';
            /* Submit button Id*/
            this.submitId = 'vesrma-save-button';
            /* Save and cont Id */
            this.saveAndContinueEditId = 'vesrma-save-and-continue';
            /* Print */
            this.printId = 'vesrma-print';
            /* Registering global object */
            this.global = typeof(globalObject) == 'undefined' ? window : globalObject;

            this.submitButtons = new Array(this.submitId, this.saveAndContinueEditId, this.printId);
        },

        disableSubmitButtons: function () {
            for (var i = 0; i < this.submitButtons.length; i++) {
                if ($(this.submitButtons[i])) {
                    $(this.submitButtons[i]).addClassName('disabled').writeAttribute('disabled', 'disabled'); } }
        },

        enableSubmitButtons: function () {
            for (var i = 0; i < this.submitButtons.length; i++) {
                if ($(this.submitButtons[i])) {
                    $(this.submitButtons[i]).removeClassName('disabled').writeAttribute('disabled', null); } }
        },

        validateForm: function () {
            this.disableSubmitButtons();
            var chPassed = true;
            $$('#'+this.formId+' '+this.itemsCountClass).each(function (element) {
                if (!this.validateItemCount('change', element)) {
                    chPassed = false; }
            }, this);

            if (!this.form || !this.form.validator || !this.form.validator.validate()) {
                this.enableSubmitButtons();
                if (typeof(rmarequest_tabsJsTabs.tabs[1]) != 'undefined') {
                    rmarequest_tabsJsTabs.tabs[1].addClassName('error'); }
                return false;
            }
            if (chPassed) {
                if (typeof(rmarequest_tabsJsTabs.tabs[1]) != 'undefined') {
                    rmarequest_tabsJsTabs.tabs[1].removeClassName('error'); }
                return this.form.submit();
            } else {
                if (typeof(rmarequest_tabsJsTabs.tabs[1]) != 'undefined') {
                    rmarequest_tabsJsTabs.tabs[1].addClassName('error'); }
                this.enableSubmitButtons();
                return false;
            }
        },

        validateItemCount: function (event, element) {
            if (typeof(element) == 'undefined') {
                element = this;
            }

            /* add error message */
            var showAdvice = function (elmId, message) {
                removeAdvice(elmId);

                $(elmId).addClassName('ves-validation-failed');

                var advice = '<div class="admin__field-error validation-advice ves-advice" id="advice-' + elmId +'" style="display:none">' + message + '</div>';

                var container = $(elmId).up();
                container.insert({
                    bottom: advice
                });
                new Effect.Appear('advice-'+elmId, {
                    duration : 1
                });

                return false;
            };

            /* removes advice message */
            var removeAdvice = function (elmId) {
                $(elmId).removeClassName('ves-validation-failed');

                if ($('advice-'+elmId)) {
                    $('advice-'+elmId).remove();
                }

                return true;
            };
            if (element.disabled) {
                return removeAdvice($(element).identify()); }

            var maxCount = $(element.identify()+'-maxcount').value;

            if (element.value != '') {
                var value = parseInt(element.value);
                if (!isNaN(value)) {
                    if (value < 0 || value > maxCount) {
                        return showAdvice($(element).identify(), 'Wrong quantity');
                    } else {
                        element.value = value;
                    }
                } else {
                    return showAdvice($(element).identify(), 'Not a number');
                }
            } else {
                return showAdvice($(element).identify(), 'Can\'t be empty');
            }

            return removeAdvice($(element).identify());
        },

        /* add onchange handler to validate items count */
        observeItemsCount: function () {
            if (this.getSelfObjectName()) {
                $$(this.itemsCountClass).each(function (obj) {
                    obj.observe('change', this.global[this.getSelfObjectName()].validateItemCount);
                }, this);
            }
        },

        /* returns self object name */
        getSelfObjectName: function () {
            for (var name in this.global) {
                if (this.global[name] == this) {
                    return name; }
            }

            return false;
        }
    });
    window.VnecomsRmaAdminRmaFormControl = VnecomsRmaAdminRmaFormControl;
    var VnecomsRmaFormAdvice = Class.create({
        initialize: function () {},

        showAdvice: function (elmId, message) {
            this.removeAdvice(elmId);

            $(elmId).addClassName('ves-validation-failed');

            var advice = '<div class="admin__field-error validation-advice ves-advice" id="advice-' + elmId +'" style="display:none">' + message + '</div>';

            // var container = $(elmId).up();
            var container = $(elmId);
            container.insert({
                after: advice
            });
            new Effect.Appear('advice-'+elmId, {
                duration : 1
            });

            return false;
        },

        removeAdvice: function (elmId) {
            $(elmId).removeClassName('ves-validation-failed');

            if ($('advice-'+elmId)) {
                $('advice-'+elmId).remove();
            }

            return true;
        }
    });

    var vnecomsRmaFormAdvice = new VnecomsRmaFormAdvice();
    window.VnecomsRmaFormAdvice = VnecomsRmaFormAdvice;

    var RequestLoadingBox = Class.create();
    RequestLoadingBox.prototype = {
        initialize: function (loadingId, overlayId) {
            this.loading    = $(loadingId);
            this.overlay    = $(overlayId);
        },
        show: function () {
            this.loading.show();
            this.overlay.show();
        },
        isShow: function () {
            return this.loading.getStyle('display')=='block';
        },
        close: function () {
            this.loading.hide();
            this.overlay.hide();
        }
    }
    window.RequestLoadingBox = RequestLoadingBox;
});

//var vnecomsRmaFormAdvice = new VnecomsRmaFormAdvice();
