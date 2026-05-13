/**
 * Add functionality to hide validation errors ("silent" validation).
 */
define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Customer/js/model/customer'
], function ($, wrapper, customer) {
    'use strict';

    return function (target) {
        target.validate = wrapper.wrapSuper(target.validate, function (hideError) {
            let result = this._super();

            if (!result && (hideError || window.silentShippingValidation) && !customer.isLoggedIn()) {
                $('form[data-role=email-with-possible-login]').validation('clearError');
            }

            return result;
        });

        return target;
    };
});
