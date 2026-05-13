define([
    'uiRegistry', 'Amasty_CheckoutCore/js/model/shipping-registry'
], function (registry, shippingRegistry) {
    'use strict';

    return {
        /**
         * Validate checkout shipping step
         *
         * @returns {Boolean}
         */
        validate: function (hideError) {
            let shipping = registry.get('checkout.steps.shipping-step.shippingAddress'),
                result;

            if (hideError && shippingRegistry.isEstimationHaveError() || !shipping.silentValidation()) {
                return false;
            }

            shipping.allowedDynamicalSave = false;
            window.silentShippingValidation = !!hideError;
            result = shipping.validateShippingInformation(hideError);

            if (hideError && !result && !window.shippingErrorHideIsNotAllowed) {
                if (shipping.isFormInline && shipping.source.get('params.invalid')) {
                    //set current value as initialValue
                    shipping.source.trigger('data.overload');
                    //set initialValue to value and reset errors
                    shipping.source.trigger('data.reset');
                }

                shipping.errorValidationMessage(false);
            }

            delete window.silentShippingValidation;
            shipping.allowedDynamicalSave = true;

            return result;
        }
    };
});
