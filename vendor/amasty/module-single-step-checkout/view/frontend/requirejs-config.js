/* eslint-disable camelcase */
var amasty_mixin_enabled = !window.amasty_checkout_disabled,
    config;

config = {
    config: {
        mixins: {
            'Magento_Checkout/js/view/billing-address': {
                'Amasty_Checkout/js/view/billing-address-mixin': amasty_mixin_enabled
            },
            'Magento_Checkout/js/view/shipping': {
                'Amasty_Checkout/js/view/shipping-mixin': amasty_mixin_enabled
            }
        }
    },
    shim: {
        'Amasty_CheckoutCore/js/view/onepage': [
            'Amasty_Checkout/js/validation/phone-validation'
        ]
    }
};
