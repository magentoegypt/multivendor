/**
 * Hub Market — the third checkout step, "Review".
 *
 * Magento ships TWO steps: shipping, then a billing step that carries payment
 * selection AND the place-order action together. The reference splits that into
 * Shipping Info / Payment / Review.
 *
 * WHY THIS DOES NOT TOUCH THE PAYMENT RENDERERS
 * ---------------------------------------------
 * Each payment method renders its own Place Order button, and its own handler
 * behind it — this store has three active, including a card gateway that may own
 * redirect/3-D Secure behaviour. Moving those buttons, or reimplementing what
 * they do, is how a checkout starts taking orders that never reach the gateway.
 *
 * So the renderers are left exactly as Magento built them. This step delegates:
 * it finds the component for the CURRENTLY SELECTED method and calls the same
 * `placeOrder()` its own button calls. Whatever a gateway does on place-order, it
 * still does — this only changes where the customer is standing when it happens.
 */
define([
    'ko',
    'uiComponent',
    'underscore',
    'uiRegistry',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/full-screen-loader',
    'mage/translate'
], function (ko, Component, _, registry, stepNavigator, quote, fullScreenLoader, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MagentoEgypt_CheckoutExtend/review-step'
        },

        initialize: function () {
            this._super();

            /*
             * The observable is created HERE, on the instance, after _super().
             *
             * NAMED `hmIsVisible`, not `isVisible`. uiElement owns `isVisible`
             * and replaces it after initialize() runs, so the navigator held one
             * observable while the template bound another: step state flipped
             * correctly and the DOM never moved. Proven — the element's context
             * IS this instance (ko.contextFor(el).$data === instance), yet
             * instance.isVisible !== registeredStep.isVisible, and setting the
             * instance's own observable did move the DOM.
             *
             * Under a name the framework does not manage, the registered
             * observable and the bound one stay the same object.
             */
            this.hmIsVisible = ko.observable(false);

            /*
             * sortOrder 30 puts it after payment (20). The step is registered
             * with the navigator rather than merely drawn, so the progress bar
             * entry is real: it can be navigated back to, and the browser's back
             * button behaves.
             */
            stepNavigator.registerStep(
                'review',
                null,
                $t('Review'),
                this.hmIsVisible,
                _.bind(this.navigate, this),
                30
            );

            return this;
        },

        navigate: function () {
            this.hmIsVisible(true);
        },

        /** Shipping address, for the review summary. */
        getShippingAddress: function () {
            return quote.shippingAddress();
        },

        /** Chosen shipping method, or null while none is set. */
        getShippingMethodTitle: function () {
            var m = quote.shippingMethod();

            if (!m) {
                return null;
            }

            return (m['carrier_title'] || '') + ' - ' + (m['method_title'] || '');
        },

        /** Chosen payment method's display title. */
        getPaymentMethodTitle: function () {
            var renderer = this.getSelectedPaymentRenderer();

            if (renderer && renderer.getTitle) {
                return renderer.getTitle();
            }

            return quote.paymentMethod() ? quote.paymentMethod().method : null;
        },

        /**
         * The component behind the selected method.
         *
         * Matched on `item.method` rather than by hard-coded registry path,
         * because a gateway is free to register its renderer wherever it likes.
         * Requiring a placeOrder() means a component that cannot place an order
         * is never mistaken for the payment one.
         */
        getSelectedPaymentRenderer: function () {
            var selected = quote.paymentMethod(),
                code = selected ? selected.method : null,
                found = null;

            if (!code) {
                return null;
            }

            _.each(registry.filter(function (component) {
                return component
                    && component.item
                    && component.item.method === code
                    && typeof component.placeOrder === 'function';
            }) || [], function (component) {
                if (!found) {
                    found = component;
                }
            });

            return found;
        },

        /** True once a payment method is chosen — the step cannot act before that. */
        canPlaceOrder: function () {
            return !!this.getSelectedPaymentRenderer();
        },

        /**
         * Hand off to the selected method's own place-order.
         *
         * If no renderer is found the customer is sent BACK to payment rather
         * than left on a dead button — that is the only state where this step
         * cannot do its job, and it is recoverable.
         */
        placeOrder: function () {
            var renderer = this.getSelectedPaymentRenderer();

            if (!renderer) {
                stepNavigator.navigateTo('payment');

                return false;
            }

            fullScreenLoader.startLoader();

            try {
                renderer.placeOrder();
            } finally {
                /*
                 * The renderer owns the loader from here — it stops it on both
                 * success and failure. This only clears the one started above so
                 * a synchronous throw cannot leave the page locked.
                 */
                fullScreenLoader.stopLoader();
            }

            return true;
        },

        backToPayment: function () {
            stepNavigator.navigateTo('payment');
        }
    });
});
