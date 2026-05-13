define([
    'jquery',
    'Magento_Checkout/js/model/full-screen-loader',
    'Vnecoms_Quotation/js/quotation-data',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, fullScreenLoader, dataQuotation, alert, $t) {
    "use strict";

    /**
     * Bidding widget for placing a bid on the quote request page.
     */
    $.widget('bidding.update', {

        /**
         * The element options:
         * - Item ID is set in the element
         * - sessionProductKey is the key used on the session
         */
        options: {
            itemId: 0,
            sessionProductKey: undefined,
            quoteUpdateUrl: ''
        },

        /**
         * Add all the events on create
         *
         * @private
         */
        _create: function () {
            this.bindInputCheck();
            /*this.initInputValue();*/
        },

        /**
         * Update the session JS data on keyup
         */
        bindInputCheck: function () {
            var self = this;
            var items = $.find("[data-role='quote-item-qty']");
            for (var i = 0; i < items.length; i++) {
                $(items[i]).on('keypress', $.proxy(function(event) {
                    var keyCode = (event.keyCode ? event.keyCode : event.which);
                    if (keyCode == 13) {
                        event.preventDefault();
                        event.stopPropagation();
                        return false;
                    }
                }, this));
            }

            var items = $.find("[data-role='quote-item-comment']");
            for (var i = 0; i < items.length; i++) {
                $(items[i]).on('keypress', $.proxy(function(event) {
                    var keyCode = (event.keyCode ? event.keyCode : event.which);
                    if (keyCode == 13) {
                        event.preventDefault();
                        event.stopPropagation();
                        return false;
                    }
                }, this));
            }

            var items = $.find("[data-role='quote-item-price']");
            for (var i = 0; i < items.length; i++) {
                $(items[i]).on('keypress', $.proxy(function(event) {
                    var keyCode = (event.keyCode ? event.keyCode : event.which);
                    if (keyCode == 13) {
                        event.preventDefault();
                        event.stopPropagation();
                        return false;
                    }
                }, this));
            }

            /*$(this.element).on('keyup', function (e) {
                e.stopPropagation();
                self.updateData();
            });*/

            $(this.element).on('change', function (e) {
                event.preventDefault();
                e.stopPropagation();
                self.updateData();
            });
        },

        /**
         * Init the input value by loading the value from the checkoutData
         */
        initInputValue: function() {
            var data = dataQuotation.getQuotationProductsFromData(),
                itemId = this.getItemId();

            if (typeof data[this.options.sessionProductKey] !== 'undefined'
                && typeof data[this.options.sessionProductKey][itemId] !== 'undefined') {
                this.setValue(data[this.options.sessionProductKey][itemId]);
            }

            /*this.toggleDisabled();*/
        },

        /**
         * Update the quote data via ajax
         */
        updateData: function () {
            var data = dataQuotation.getQuotationProductsFromData(),
                itemId = this.getItemId(),
                value = this.getValue();

            if (data.length == 0) {
                data = {};
            }

            if (value == undefined || !value) {
                return;
            }

            if (typeof data[this.options.sessionProductKey] === 'undefined') {
                data[this.options.sessionProductKey] = {};
            }

            data[this.options.sessionProductKey][itemId] = value;
            dataQuotation.setQuotationProductsFromData(data);

            var quoteData = {
                form_key: window.checkoutConfig.formKey,
                quotation_product_data: JSON.stringify(dataQuotation.getQuotationProductsFromData())
            };

            $.ajax({
                url: this.options.quoteUpdateUrl,
                method: "POST",
                data: quoteData,
                context: $('body'),
                dataType: "json",
                showLoader: true
            }).done(function(response){
                if(response.error){
                    console.log(response.message);
                }
            }).fail(function () {
                alert({
                    title: $t('Error'),
                    content: $t('Something wrong. Please try to refresh the page.')
                });
            });

        },

        /**
         * Save the new price to the session
         */
        saveData: function () {
            updateQuote().done(function() {
                fullScreenLoader.stopLoader(true);
            });
        },

        /**
         * Get the quote item id
         * @returns {number}
         */
        getItemId: function () {
            return this.options.itemId;
        },

        /**
         * Get the element price
         * @returns {*|jQuery}
         */
        getValue: function () {
            return $(this.element).val();
        },

        /**
         * Set value to the input
         * @param value
         */
        setValue: function (value) {
            $(this.element).val(value);
        },

        /**
         * Toggle disabled
         */
        toggleDisabled: function () {
            $(this.element).prop('disabled', function(i, v) { return !v; });
        }
    });

    return $.bidding.update;
});
