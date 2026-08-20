/**
 * Hub Market — "Continue to Review", rendered in the payment step's afterMethods
 * region.
 *
 * With ordering moved to the third step, the payment step needs a way forward
 * that is NOT a place-order. It is disabled until a method is chosen, so the
 * customer cannot reach a review step that has nothing to place.
 */
define([
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/quote'
], function (ko, Component, stepNavigator, quote) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MagentoEgypt_CheckoutExtend/continue-to-review'
        },

        /** A method must be selected before the review step can do anything. */
        isReady: ko.computed(function () {
            return !!quote.paymentMethod();
        }),

        /**
         * The reference pairs the forward action with a Back. navigateTo() only
         * moves to a step it considers processed, which shipping always is by
         * the time this button is on screen.
         */
        backToShipping: function () {
            stepNavigator.navigateTo('shipping');

            return true;
        },

        continueToReview: function () {
            if (!quote.paymentMethod()) {
                return false;
            }

            stepNavigator.next();

            return true;
        }
    });
});
