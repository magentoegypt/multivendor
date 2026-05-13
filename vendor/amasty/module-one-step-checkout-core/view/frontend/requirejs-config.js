/* jshint browser:true jquery:true */
var amasty_mixin_enabled = !window.amasty_checkout_disabled,
    config;

config = {
    'map': { '*': {} },
    config: {
        mixins: {
            'Magento_Checkout/js/model/new-customer-address': {
                'Amasty_CheckoutCore/js/model/new-customer-address-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/view/payment/list': {
                'Amasty_CheckoutCore/js/view/payment/list': amasty_mixin_enabled
            },
            'Magento_Checkout/js/view/summary/abstract-total': {
                'Amasty_CheckoutCore/js/view/summary/abstract-total': amasty_mixin_enabled
            },
            'Magento_Checkout/js/model/step-navigator': {
                'Amasty_CheckoutCore/js/model/step-navigator-mixin': amasty_mixin_enabled
            },
            'Magento_Paypal/js/action/set-payment-method': {
                'Amasty_CheckoutCore/js/action/set-payment-method-mixin': amasty_mixin_enabled
            },
            'Magento_CheckoutAgreements/js/model/agreements-assigner': {
                'Amasty_CheckoutCore/js/model/agreements-assigner-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/view/summary': {
                'Amasty_CheckoutCore/js/view/summary-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/view/shipping': {
                'Amasty_CheckoutCore/js/view/shipping-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/view/summary/cart-items': {
                'Amasty_CheckoutCore/js/view/summary/cart-items-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/model/payment/additional-validators': {
                'Amasty_CheckoutCore/js/model/payment-validators/additional-validators-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/model/customer-email-validator': {
                'Amasty_CheckoutCore/js/model/customer-email-validator-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/model/checkout-data-resolver': {
                'Amasty_CheckoutCore/js/model/checkout-data-resolver-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/model/shipping-rates-validator': {
                'Amasty_CheckoutCore/js/model/shipping-rates-validator-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/action/set-shipping-information': {
                'Amasty_CheckoutCore/js/action/set-shipping-information-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/model/full-screen-loader': {
                'Amasty_CheckoutCore/js/model/full-screen-loader-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/model/shipping-rate-processor/new-address': {
                'Amasty_CheckoutCore/js/model/default-shipping-rate-processor-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/view/payment': {
                'Amasty_CheckoutCore/js/view/payment-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/model/payment-service': {
                'Amasty_CheckoutCore/js/model/payment-service-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/model/address-converter': {
                'Amasty_CheckoutCore/js/model/address-converter-mixin': amasty_mixin_enabled
            },
            'Magento_Paypal/js/view/payment/method-renderer/in-context/checkout-express': {
                'Amasty_CheckoutCore/js/view/payment/method-renderer/in-context/checkout-express-mixin':
                    amasty_mixin_enabled
            },

            // in Magento 2.4 module Magento_Braintree renamed to Paypal_Braintree
            'Magento_Braintree/js/view/payment/method-renderer/paypal': {
                'Amasty_CheckoutCore/js/view/payment/method-renderer/braintree/paypal-mixin':
                    amasty_mixin_enabled
            },
            'PayPal_Braintree/js/view/payment/method-renderer/paypal': {
                'Amasty_CheckoutCore/js/view/payment/method-renderer/braintree/paypal-mixin':
                    amasty_mixin_enabled
            },
            'Magento_Braintree/js/view/payment/method-renderer/cc-form': {
                'Amasty_CheckoutCore/js/view/payment/method-renderer/braintree/cc-form-mixin': amasty_mixin_enabled
            },
            'PayPal_Braintree/js/view/payment/method-renderer/cc-form': {
                'Amasty_CheckoutCore/js/view/payment/method-renderer/braintree/cc-form-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/view/billing-address': {
                'Amasty_CheckoutCore/js/view/billing-address-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/view/payment/default': {
                'Amasty_CheckoutCore/js/view/payment/method-renderer/default-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/model/shipping-rate-registry': {
                'Amasty_CheckoutCore/js/model/shipping-rate-registry-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/view/shipping-address/address-renderer/default': {
                'Amasty_CheckoutCore/js/view/shipping-address/address-renderer/default-mixin': amasty_mixin_enabled
            },
            'Amasty_Gdpr/js/model/consents-assigner': {
                'Amasty_CheckoutCore/js/model/consents-assigner-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/action/select-payment-method': {
                // Disable hardcoded save payment information
                // @see Amasty_CheckoutCore/js/model/payment/salesrule-observer
                'Magento_SalesRule/js/action/select-payment-method-mixin': !amasty_mixin_enabled
            }
        }
    }
};

if (amasty_mixin_enabled) {
    config.map['*'] = {
        checkoutCollapsibleSteps: 'Amasty_CheckoutCore/js/view/checkout/design/collapsible-steps',
        summaryWidget: 'Amasty_CheckoutCore/js/view/summary/summary-widget',
        stickyWidget: 'Amasty_CheckoutCore/js/view/summary/sticky-widget',
        'Magento_Checkout/template/payment-methods/list.html': 'Amasty_CheckoutCore/template/payment-methods/list.html',
        'Magento_Checkout/template/billing-address/details.html':
            'Amasty_CheckoutCore/template/onepage/billing-address/details.html',
        'Magento_Checkout/js/action/get-totals': 'Amasty_CheckoutCore/js/action/get-totals',
        'Magento_Checkout/js/model/shipping-rate-service': 'Amasty_CheckoutCore/js/model/shipping-rate-service-override',
        'Magento_Checkout/js/action/recollect-shipping-rates': 'Amasty_CheckoutCore/js/action/recollect-shipping-rates'
    };
}
