define([
    'jquery',
    'underscore',
    'ko',
    'Magento_Customer/js/customer-data'
], function ($, _, ko, customerData) {

    $( document ).ready(function() {
        var sections;
alert('ran here');
        sections = ["cart"];
        if (sections) {
            customerData.invalidate(sections);
        }
    });

});