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
    'Magento_Catalog/js/price-utils',
    'mage/translate'
], function (ko, Component, _, registry, stepNavigator, quote, fullScreenLoader, priceUtils, $t) {
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

        /** True when the cart has nothing to ship. */
        isVirtualOrder: function () {
            return !!quote.isVirtual();
        },

        /**
         * The address this order is actually identified by.
         *
         * A virtual or downloadable order has no shipping address — Magento
         * never collects one. Reading quote.shippingAddress() there returns an
         * EMPTY address object, which is truthy, so the review rendered a
         * "Shipping Address" heading with nothing underneath it and the customer
         * never saw the billing address they had just typed, immediately before
         * being asked to pay.
         *
         * @return {Object|null}
         */
        getAddress: function () {
            return this.isVirtualOrder() ? quote.billingAddress() : quote.shippingAddress();
        },

        /** Heading for that block — the two order types name it differently. */
        getAddressHeading: function () {
            return this.isVirtualOrder() ? $t('Billing Address') : $t('Shipping Address');
        },

        /**
         * Whether the block is worth drawing at all.
         *
         * Guards on CONTENT, not on the object existing, because both
         * quote.shippingAddress() and quote.billingAddress() return an empty
         * object rather than null before they are filled in — which is exactly
         * how the empty heading got on screen.
         *
         * @return {Boolean}
         */
        hasAddress: function () {
            var a = this.getAddress();

            if (!a) {
                return false;
            }

            return !!(a.city || a.postcode || (a.street && a.street.join('')));
        },

        /** Kept for compatibility with anything still calling the old name. */
        getShippingAddress: function () {
            return this.getAddress();
        },

        /** Recipient name, from the shipping address. */
        getRecipient: function () {
            var a = this.getAddress();

            if (!a) {
                return '';
            }

            return [a.firstname, a.lastname].filter(Boolean).join(' ');
        },

        /** Street, which Magento models as an array of lines. */
        getStreet: function () {
            var a = this.getAddress();

            if (!a || !a.street) {
                return '';
            }

            return _.filter(a.street, function (line) {
                return line;
            }).join(', ');
        },

        /** "City, Region Postcode" on one line, skipping whatever is missing. */
        getCityLine: function () {
            var a = this.getAddress(),
                tail;

            if (!a) {
                return '';
            }

            tail = [a.region, a.postcode].filter(Boolean).join(' ');

            return [a.city, tail].filter(Boolean).join(', ');
        },

        /**
         * Guest checkout keeps the address off the quote object, so fall back
         * through the places Magento actually puts an email.
         */
        getEmail: function () {
            var a = this.getAddress(),
                config = window.checkoutConfig || {};

            return quote.guestEmail
                || (a && a.email)
                || (config.customerData && config.customerData.email)
                || '';
        },

        /**
         * Cart lines for the review list. Names and totals come from
         * quoteItemData; thumbnails live in a separate imageData map keyed by
         * item id, which is the same pairing the order summary uses.
         */
        getItems: function () {
            var config = window.checkoutConfig || {},
                images = config.imageData || {},
                format = quote.getPriceFormat ? quote.getPriceFormat() : undefined;

            return _.map(config.quoteItemData || [], function (item) {
                var image = images[item['item_id']] || {},
                    total = item['row_total_incl_tax'];

                if (total === undefined || total === null) {
                    total = item['row_total'];
                }

                return {
                    name: item.name,
                    qty: item.qty,
                    price: priceUtils.formatPrice(total, format),
                    src: image.src || null
                };
            });
        },

        /** Chosen shipping method, or null while none is set. */
        getShippingMethodTitle: function () {
            var m = quote.shippingMethod();

            if (!m) {
                return null;
            }

            return (m['carrier_title'] || '') + ' - ' + (m['method_title'] || '');
        },

        /**
         * Chosen payment method's display title.
         *
         * Reads hmIsVisible() so the binding re-evaluates every time the step is
         * shown. The renderer lookup goes through uiRegistry, which Knockout
         * cannot track: evaluated only when quote.paymentMethod() changed, a
         * lookup made before the renderer existed stuck, and the step printed the
         * raw code ("cashondelivery") — the visible tell of TC65.
         */
        getPaymentMethodTitle: function () {
            var renderer;

            this.hmIsVisible();
            renderer = this.getSelectedPaymentRenderer();

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

        /**
         * Enables the Place Order button: the step is on screen and a method is chosen.
         *
         * Deliberately NOT "a renderer is found" any more (CL036-TC65). That
         * lookup reads uiRegistry, which Knockout cannot track, so the `enable`
         * binding only re-ran when quote.paymentMethod() changed. When the method
         * was set before its renderer registered — a logged-in customer whose
         * quote already carried it, while the payment list was still loading —
         * the button stayed disabled for good. It still LOOKED green, and blank
         * gives disabled buttons pointer-events: none, so clicks went nowhere:
         * no request, no message. The lookup now happens at click time, below,
         * which already knows what to do when it fails.
         */
        canPlaceOrder: function () {
            return this.hmIsVisible() && !!quote.paymentMethod();
        },

        /**
         * Hand off to the selected method's own place-order.
         *
         * If no renderer is found the customer is sent BACK to payment rather
         * than left on a dead button — that is the only state where this step
         * cannot do its job, and it is recoverable.
         *
         * Same when the renderer REFUSES. Its placeOrder() returns false without a
         * word here when its own form fails validation or no billing address is
         * set — and every message it shows about that renders inside the payment
         * step, which is hidden on this one. Going back puts the customer where
         * those messages are. The exception is an order already in flight (a
         * double click): the renderer refuses that too, and must be left alone.
         */
        placeOrder: function () {
            var renderer = this.getSelectedPaymentRenderer(),
                started = false,
                wasAllowed;

            if (!renderer) {
                stepNavigator.navigateTo('payment');

                return false;
            }

            wasAllowed = typeof renderer.isPlaceOrderActionAllowed === 'function'
                ? renderer.isPlaceOrderActionAllowed()
                : true;

            if (!wasAllowed && quote.billingAddress()) {
                /* Billing is set, so "not allowed" means an order is already being placed. */
                return false;
            }

            fullScreenLoader.startLoader();

            try {
                started = renderer.placeOrder();
            } finally {
                /*
                 * The renderer owns the loader from here — it stops it on both
                 * success and failure. This only clears the one started above so
                 * a synchronous throw cannot leave the page locked.
                 */
                fullScreenLoader.stopLoader();
            }

            if (!started) {
                stepNavigator.navigateTo('payment');
            }

            return started;
        },

        backToPayment: function () {
            stepNavigator.navigateTo('payment');
        }
    });
});
