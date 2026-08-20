/**
 * Hub Market theme RequireJS map.
 *
 * Theme-level JS lives at web/js/ and is published to
 * pub/static/frontend/MagentoEgypt/hub-market/<locale>/js/, which is directly
 * under RequireJS's baseUrl — so the target is a plain `js/<name>` path with no
 * module namespace in front of it. The alias exists so templates can say
 * `{"hmCartQty": {}}` in data-mage-init instead of hard-coding that path.
 */
var config = {
    map: {
        '*': {
            hmCartQty: 'js/hm-cart-qty',
            hmMinicartQty: 'js/hm-minicart-qty',

            /*
             * Same Amasty timing bug as the mixins below, but via `map`: their
             * config does `if (amasty_mixin_enabled) { config.map['*'] = {...} }`,
             * which swaps core checkout modules and templates for Amasty's.
             * Mapping each path back to itself restores core, since a later
             * config wins on the same key.
             *
             * Without this the payment step renders Amasty's list.html against
             * core's list component: the template binds `isLoading`, core has no
             * such property, Knockout throws and NO payment method renders.
             */
            'Magento_Checkout/template/payment-methods/list.html':
                'Magento_Checkout/template/payment-methods/list.html',
            'Magento_Checkout/template/billing-address/details.html':
                'Magento_Checkout/template/billing-address/details.html',
            'Magento_Checkout/js/action/get-totals':
                'Magento_Checkout/js/action/get-totals',
            'Magento_Checkout/js/model/shipping-rate-service':
                'Magento_Checkout/js/model/shipping-rate-service',
            'Magento_Checkout/js/action/recollect-shipping-rates':
                'Magento_Checkout/js/action/recollect-shipping-rates'
        }
    },

    /*
     * Amasty One Step Checkout is installed but switched OFF
     * (amasty_checkout/general/enabled = 0), so none of its JS mixins should
     * load. Amasty guards them with `!window.amasty_checkout_disabled` and sets
     * that flag from amastyCheckoutDisabled.js -- but the plugin that injects
     * that file lands it AFTER requirejs-config.js in the head, so the guard
     * always reads `undefined` and every mixin loads anyway.
     *
     * The damage is step-navigator-mixin: it wraps registerStep and swaps the
     * caller's observable for a brand new `ko.observable(isVisible())`. The
     * navigator then owns an observable nothing is bound to, so next() flips it
     * and the DOM never moves -- no checkout step could ever advance.
     *
     * Disabling them here reproduces the state Amasty intended, at a point in
     * the merge where it actually takes effect. If Amasty checkout is ever
     * enabled in admin, DELETE this block -- it would break their checkout.
     */
    config: {
        mixins: {
            'Amasty_Gdpr/js/model/consents-assigner': {
                'Amasty_CheckoutCore/js/model/consents-assigner-mixin': false
            },
            'Magento_Braintree/js/view/payment/method-renderer/cc-form': {
                'Amasty_CheckoutCore/js/view/payment/method-renderer/braintree/cc-form-mixin': false
            },
            'Magento_Braintree/js/view/payment/method-renderer/paypal': {
                'Amasty_CheckoutCore/js/view/payment/method-renderer/braintree/paypal-mixin': false
            },
            'Magento_Checkout/js/action/set-shipping-information': {
                'Amasty_CheckoutCore/js/action/set-shipping-information-mixin': false
            },
            'Magento_Checkout/js/model/address-converter': {
                'Amasty_CheckoutCore/js/model/address-converter-mixin': false
            },
            'Magento_Checkout/js/model/checkout-data-resolver': {
                'Amasty_CheckoutCore/js/model/checkout-data-resolver-mixin': false
            },
            'Magento_Checkout/js/model/customer-email-validator': {
                'Amasty_CheckoutCore/js/model/customer-email-validator-mixin': false
            },
            'Magento_Checkout/js/model/full-screen-loader': {
                'Amasty_CheckoutCore/js/model/full-screen-loader-mixin': false
            },
            'Magento_Checkout/js/model/new-customer-address': {
                'Amasty_CheckoutCore/js/model/new-customer-address-mixin': false
            },
            'Magento_Checkout/js/model/payment-service': {
                'Amasty_CheckoutCore/js/model/payment-service-mixin': false
            },
            'Magento_Checkout/js/model/payment/additional-validators': {
                'Amasty_CheckoutCore/js/model/payment-validators/additional-validators-mixin': false
            },
            'Magento_Checkout/js/model/shipping-rate-processor/new-address': {
                'Amasty_CheckoutCore/js/model/default-shipping-rate-processor-mixin': false
            },
            'Magento_Checkout/js/model/shipping-rate-registry': {
                'Amasty_CheckoutCore/js/model/shipping-rate-registry-mixin': false
            },
            'Magento_Checkout/js/model/shipping-rates-validator': {
                'Amasty_CheckoutCore/js/model/shipping-rates-validator-mixin': false
            },
            /*
             * Amasty turns this core mixin OFF while their checkout is on
             * (`!amasty_mixin_enabled`). Their checkout is off, so it belongs on.
             */
            'Magento_Checkout/js/action/select-payment-method': {
                'Magento_SalesRule/js/action/select-payment-method-mixin': true
            },
            'Magento_Checkout/js/model/step-navigator': {
                'Amasty_CheckoutCore/js/model/step-navigator-mixin': false
            },
            'Magento_Checkout/js/view/billing-address': {
                'Amasty_Checkout/js/view/billing-address-mixin': false,
                'Amasty_CheckoutCore/js/view/billing-address-mixin': false
            },
            'Magento_Checkout/js/view/payment': {
                'Amasty_CheckoutCore/js/view/payment-mixin': false
            },
            'Magento_Checkout/js/view/payment/default': {
                'Amasty_CheckoutCore/js/view/payment/method-renderer/default-mixin': false
            },
            'Magento_Checkout/js/view/payment/list': {
                'Amasty_CheckoutCore/js/view/payment/list': false
            },
            'Magento_Checkout/js/view/shipping': {
                'Amasty_Checkout/js/view/shipping-mixin': false,
                'Amasty_CheckoutCore/js/view/shipping-mixin': false
            },
            'Magento_Checkout/js/view/shipping-address/address-renderer/default': {
                'Amasty_CheckoutCore/js/view/shipping-address/address-renderer/default-mixin': false
            },
            'Magento_Checkout/js/view/summary': {
                'Amasty_CheckoutCore/js/view/summary-mixin': false
            },
            'Magento_Checkout/js/view/summary/abstract-total': {
                'Amasty_CheckoutCore/js/view/summary/abstract-total': false,
                //  Keeps the summary totals visible on step one — see the file.
                'Magento_Checkout/js/summary-total-mixin': true
            },
            'Magento_Checkout/js/view/summary/cart-items': {
                'Amasty_CheckoutCore/js/view/summary/cart-items-mixin': false
            },
            'Magento_CheckoutAgreements/js/model/agreements-assigner': {
                'Amasty_CheckoutCore/js/model/agreements-assigner-mixin': false
            },
            'Magento_Paypal/js/action/set-payment-method': {
                'Amasty_CheckoutCore/js/action/set-payment-method-mixin': false
            },
            'Magento_Paypal/js/view/payment/method-renderer/in-context/checkout-express': {
                'Amasty_CheckoutCore/js/view/payment/method-renderer/in-context/checkout-express-mixin': false
            },
            'PayPal_Braintree/js/view/payment/method-renderer/cc-form': {
                'Amasty_CheckoutCore/js/view/payment/method-renderer/braintree/cc-form-mixin': false
            },
            'PayPal_Braintree/js/view/payment/method-renderer/paypal': {
                'Amasty_CheckoutCore/js/view/payment/method-renderer/braintree/paypal-mixin': false
            }
        }
    }
};
