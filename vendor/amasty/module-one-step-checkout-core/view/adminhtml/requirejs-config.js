/* jshint browser:true jquery:true */
/* global alert */
var config = {
    map: {
        '*': {
            amastySectionsRate: 'Amasty_CheckoutCore/js/reports/sections-rate',
            amCharts: 'Amasty_CheckoutCore/vendor/amcharts/amcharts',
            amChartsSerial: 'Amasty_CheckoutCore/vendor/amcharts/serial'
        }
    },
    shim: {
        'Amasty_CheckoutCore/vendor/amcharts/serial': [ 'Amasty_CheckoutCore/vendor/amcharts/amcharts' ]
    }
};
