/**
 * Hub Market — a "Billing" step, for virtual carts only.
 *
 * A cart of only virtual or downloadable items has no shipping step, so Magento
 * leaves it with two: Payment (which quietly carries the billing address form)
 * and this theme's Review. That reads as two steps where a physical cart shows
 * three, and it buries the one address the order will ever have inside the
 * payment panel.
 *
 * So on a virtual quote the billing form is lifted out of the payment component
 * (see RemoveBillingAddress, which relocates the node in jsLayout) and given its
 * own step ahead of Payment. Physical carts are untouched and keep
 * Shipping Info -> Payment -> Review.
 *
 * WHY quote.isVirtual() AND NOT A PHP FLAG ON THE TEMPLATE
 * -------------------------------------------------------
 * `checkoutConfig.quoteData.is_virtual` is already shipped to the browser and
 * `Magento_Checkout/js/model/quote` exposes it as isVirtual(). Measured on a
 * virtual cart: is_virtual = 1, quote.isVirtual() === true, and the navigator
 * holds only payment(20) and review(30) — shipping never registers. Reading the
 * flag the checkout already has avoids a second source of truth that could
 * disagree with the one Magento itself branches on.
 *
 * WHY hmIsVisible AND NOT isVisible
 * ---------------------------------
 * Same trap review-step.js documents: uiElement owns `isVisible` and replaces it
 * after initialize() runs, so the navigator would hold one observable while the
 * template bound another — step state would flip and the DOM would never move.
 * Under a name the framework does not manage, both stay the same object.
 *
 * WHY THE FORM IS NOT REBUILT HERE
 * --------------------------------
 * The node moved is Magento's own `billing-address-form`, complete with its
 * `billingAddressList` child. Its one external-looking link,
 * `billingAddressListProvider: '${$.name}.billingAddressList'`, resolves against
 * the component's OWN name, so it survives being reparented. Rebuilding the form
 * would mean owning address validation and selectBillingAddress() by hand, which
 * is how a checkout starts taking orders with no usable billing address.
 */
define([
    'ko',
    'uiComponent',
    'underscore',
    'uiRegistry',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/quote',
    'mage/translate'
], function (ko, Component, _, registry, stepNavigator, quote, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MagentoEgypt_CheckoutExtend/billing-step'
        },

        initialize: function () {
            this._super();

            this.hmIsVisible = ko.observable(false);

            //  Physical carts already have Shipping Info in this slot, and their
            //  billing address is derived from it. Registering here would give
            //  them a fourth step asking for something they never enter.
            if (!quote.isVirtual()) {
                return this;
            }

            //  sortOrder 10 is the slot shipping would have taken. Safe to reuse:
            //  on a virtual quote shipping never registers at all, so the two can
            //  never both be present.
            stepNavigator.registerStep(
                'hm-billing',
                null,
                $t('Billing'),
                this.hmIsVisible,
                _.bind(this.navigate, this),
                10
            );

            return this;
        },

        navigate: function () {
            this.hmIsVisible(true);
        },

        /**
         * Magento's billing-address component, wherever it ended up.
         *
         * Found by duck-typing on dataScopePrefix + updateAddress() rather than
         * by registry path, because the path depends on which parent the node was
         * moved under and on the display_billing_address_on setting (shared form
         * vs one per payment method).
         *
         * @return {Object|null}
         */
        getBillingComponent: function () {
            var found = null;

            registry.filter(function (component) {
                if (found || !component) {
                    return false;
                }

                if (typeof component.updateAddress === 'function'
                    && typeof component.dataScopePrefix === 'string'
                    && component.dataScopePrefix.indexOf('billingAddress') === 0) {
                    found = component;
                }

                return false;
            });

            return found;
        },

        /**
         * Commit the address, then move on — but only if it took.
         *
         * updateAddress() validates and, on success, calls selectBillingAddress()
         * which is what actually puts the address on the quote. On failure it
         * sets params.invalid and returns having changed nothing, leaving the
         * field errors on screen. Advancing is therefore gated on the quote
         * having a billing address afterwards, not on updateAddress() having been
         * called — otherwise a shopper could walk past an invalid form and reach
         * Place Order with no address, which is the defect this whole step exists
         * to prevent.
         *
         * @return {Boolean}
         */
        continueToPayment: function () {
            var billing = this.getBillingComponent();

            if (billing) {
                billing.updateAddress();
            }

            if (!quote.billingAddress()) {
                return false;
            }

            stepNavigator.next();

            return true;
        }
    });
});
