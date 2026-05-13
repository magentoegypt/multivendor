/**
 * Optimized replacement of Magento_SalesRule/js/action/select-payment-method-mixin
 * Payment save will be triggered only if object of payment method is changed.
 * Originally, payment save was triggered on any update action, even if there was same data.
 * Which causes a lot of unnecessary requests for One Step Checkout
 */
define([
    'jquery',
    'Magento_Checkout/js/model/quote',
    'Magento_SalesRule/js/model/payment/discount-messages',
    'Magento_Checkout/js/action/set-payment-information-extended',
    'Magento_Checkout/js/action/get-totals',
    'Magento_SalesRule/js/model/coupon'
],function ($, quote, messageContainer, setPaymentInformationExtended, getTotalsAction, coupon) {
    'use strict';

    return {
        /**
         * @var {string|null}
         */
        savedMethod: null,

        /**
         * @returns {void}
         */
        initialize: function () {
            let currentMethod = quote.paymentMethod();

            quote.paymentMethod.subscribe(this.observer.bind(this));

            if (currentMethod) {
                this.savedMethod = currentMethod.method;
            }
        },

        /**
         * Quote payment method subscriber.
         *
         * @param {Object|null} paymentMethod
         * @returns {void}
         */
        observer: function (paymentMethod) {
            if (paymentMethod === null || paymentMethod.method === this.savedMethod) {
                return;
            }

            $.when(
                setPaymentInformationExtended(
                    messageContainer,
                    {
                        method: paymentMethod.method
                    },
                    true
                )
            ).done(
                this.updateTotals.bind(this)
            );

            this.savedMethod = paymentMethod.method;
        },

        /**
         * Emulate Magento behavior
         *
         * @returns {void}
         */
        updateTotals: function () {
            let deferred = $.Deferred();

            getTotalsAction([], deferred);
            $.when(deferred).done(this.updateCoupon.bind(this));
        },

        /**
         * Emulate Magento behavior
         *
         * @returns {void}
         */
        updateCoupon: function () {
            if (quote.totals() && !quote.totals()['coupon_code']) {
                coupon.setCouponCode('');
                coupon.setIsApplied(false);
            }
        }
    };
});
