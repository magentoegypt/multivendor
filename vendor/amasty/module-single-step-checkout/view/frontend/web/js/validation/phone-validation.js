require([
    'jquery',
    'Magento_Ui/js/lib/validation/validator',
    'Magento_Ui/js/lib/validation/utils',
    'mage/translate'
], function ($, validator,utils) {
    'use strict';

    validator.addRule(
        'validate-numbers-and-spec-characters',
        function (value) {
            return utils.isEmptyNoTrim(value) || (/^[\d\W\_]+$/).test(value.trim());
        },
        $.mage.__('Please use only numbers or special characters in this field.')
    );
});
