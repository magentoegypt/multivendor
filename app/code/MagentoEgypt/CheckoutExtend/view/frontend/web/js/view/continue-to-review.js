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
    'Magento_Checkout/js/model/quote',
    'Magento_Ui/js/model/messageList',
    'mage/translate'
], function (ko, Component, stepNavigator, quote, messageList, $t) {
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

        /**
         * CL036-TC34 — "Clicking Continue does not proceed ... with no error
         * message or progress."
         *
         * This used to be a silent dead end, and in two ways at once.
         *
         * The template bound `enable: isReady()`, so with no payment method
         * chosen Knockout set `disabled` on the button — and blank ships
         * `.action.primary[disabled] { opacity: .5; cursor: default;
         * pointer-events: none }`. A disabled button here is CLICK-TRANSPARENT:
         * the event never reaches this handler, so the `return false` below was
         * unreachable and the customer got no response of any kind. The same
         * pointer-events trap is on record in this codebase from the header
         * search.
         *
         * That is bad enough when the customer simply has not picked a method.
         * It is worse in the case the ticket was actually filed for, where the
         * payment step had not finished rendering at all: there was nothing to
         * pick, so the button could never enable, and pressing it did nothing
         * for ever with nothing on screen to say why.
         *
         * So the button now stays clickable and says what is missing. The
         * template carries a `--waiting` class instead of `disabled`, which
         * keeps the dimmed look without removing pointer events.
         */
        continueToReview: function () {
            if (!quote.paymentMethod()) {
                messageList.addErrorMessage({
                    message: $t('Please choose a payment method before continuing.')
                });

                this.scrollToPayment();

                return false;
            }

            //  Clear anything left from an earlier press before moving on, so
            //  the review step is not entered under a stale error.
            messageList.clear();
            stepNavigator.next();

            return true;
        },

        /**
         * Put the payment list on screen with the message. Without this the
         * error can be appended above the fold on a long checkout and the press
         * still reads as "nothing happened".
         */
        scrollToPayment: function () {
            var el = document.getElementById('checkout-payment-method-load')
                || document.querySelector('.payment-methods');

            if (el && el.scrollIntoView) {
                el.scrollIntoView({block: 'center', behavior: 'smooth'});
            }
        }
    });
});
