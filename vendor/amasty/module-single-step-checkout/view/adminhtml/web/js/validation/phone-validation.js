require([
    'jquery',
    'mage/translate',
    'jquery/validate'
], function ($) {
    'use strict';

    $.validator.addMethod(
        'validate-numbers-and-spec-characters',
        function (value) {
            return $.mage.isEmptyNoTrim(value) || (/^[\d\W\_]+$/).test(value.trim());
        },
        $.mage.__('Please use only numbers or special characters in this field.')
    );
});