/**
 * By default Magento flow, when payment method selected billing address is updates.
 * And when billing address updates, isPlaceOrderActionAllowed also update.
 * But One Step Checkout optimize billing address KO update. @see onepage.replaceEqualityComparer
 * So we need update isPlaceOrderActionAllowed on payment method change, to emulate default flow.
 * Also we added placeOrderState for Place Order button. Thus, we can flexibly manage its state.
 */
define([
    'jquery',
    'ko',
    'underscore',
    'Magento_Checkout/js/model/quote',
    'Amasty_CheckoutCore/js/model/payment/place-order-state',
    'Amasty_CheckoutCore/js/model/payment/payment-loading'
], function ($, ko, _, quote, placeOrderState, paymentLoader) {
    'use strict';

    return function (Component) {
        return Component.extend({
            defaults: {
                placeOrderButtonSelector: '#checkout-payment-method-load .action.checkout',
                paymentBlockSelector: '#checkout-payment-method-load'
            },

            isPlaceOrderActionAllowed: ko.pureComputed({
                read: function () {
                    return quote.billingAddress() !== null && placeOrderState();
                },
                write: function (value) {
                    return value;
                },
                owner: this
            }),

            initialize: function () {
                this._super();
                this.initPaymentSubscriber();

                quote.billingAddress.subscribe(function (address) {
                    this.isPlaceOrderActionAllowed(address !== null && placeOrderState());
                }, this);

                paymentLoader.subscribe(this.blockPaymentBlock, this);

                return this;
            },

            initPaymentSubscriber: _.once(function () {
                quote.paymentMethod.subscribe(this.updateIsPlaceOrderActionAllowed, this);
            }),

            updateIsPlaceOrderActionAllowed: function () {
                this.isPlaceOrderActionAllowed(quote.billingAddress() !== null && placeOrderState());
            },

            /**
             * Toggle place order button or payment block
             * @param {Boolean}  state
             * @returns {void}
             */
            blockPaymentBlock: function (state) {
                var visiblePlaceOrderButtons = $(this.placeOrderButtonSelector + ':visible');

                if (visiblePlaceOrderButtons.length) {
                    visiblePlaceOrderButtons.prop('disabled', state);
                } else {
                    $(this.paymentBlockSelector).toggleClass('-am-blocked', state);
                }
            }
        });
    };
});
