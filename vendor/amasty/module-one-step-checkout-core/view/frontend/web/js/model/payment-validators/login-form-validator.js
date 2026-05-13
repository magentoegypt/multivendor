define(
    [
        'jquery',
        'underscore',
        'uiRegistry',
        'Magento_Customer/js/model/customer'
    ],
    function ($, _, registry, customer) {
        'use strict';

        return {
            /**
             * Validate Login Form on checkout if available
             *
             * @returns {Boolean}
             */
            validate: function (hideError) {
                let createAcc = +window?.checkoutConfig?.quoteData?.additional_options?.create_account,
                    $loginForm,
                    customerEmail;

                if (customer.isLoggedIn() || createAcc <= 1) {
                    return true;
                }

                $loginForm = $('form[data-role=email-with-possible-login]');

                if (createAcc === 3) {
                    customerEmail = registry.get('checkout.steps.shipping-step.shippingAddress.customer-email')
                    if (!_.isUndefined(customerEmail) && customerEmail.isPassword()) {
                        return this._validateElement($loginForm, hideError);
                    }
                }

                if ($loginForm.find('#customer-password').val()) {
                    return this._validateElement($loginForm, hideError);
                }

                return true;
            },

            _validateElement: function ($loginForm, hideError) {
                let isValid = $loginForm.validation() && $loginForm.validation('isValid');

                if (hideError) {
                    $loginForm.validation('clearError');
                }

                return isValid;
            }
        };
    }
);
