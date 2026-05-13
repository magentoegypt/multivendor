/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, storage) {
    'use strict';

    var cacheKey = 'quotation-data';

    /**
     * Get the data from the customer
     * @returns {*}
     */
    var getData = function () {
        return storage.get(cacheKey)();
    };

    /**
     * Save the data to the customer
     * @param checkoutData
     */
    var saveData = function (checkoutData) {
        // if (typeof storage === "undefined") return;
        try {
            storage.set(cacheKey, checkoutData);
        }
        catch(err) {
        }
    };

    /**
     * Get data from checkout config
     *
     * @param data
     * @returns {*}
     */
    var getCheckoutConfig = function (data) {
        var checkoutConfigData = checkoutConfig[data];
        if (typeof checkoutConfigData === "undefined") {
            checkoutConfigData = {};
        }

        return checkoutConfigData;
    };

    /**
     * Init data
     */
    if (getData()) {
        var currentData = getData();
        currentData.quotation_product_data = getCheckoutConfig("quotation_product_data");
        saveData(currentData);
    }

    /**
     * This model provides functions to read and write to the quotation checkout data
     */
    return {

        /**
         * Set quotation product data
         * @param data
         */
        setQuotationProductsFromData: function (data) {
            var obj = getData();
            obj.quotation_product_data = data;
            saveData(obj);
        },

        /**
         * Get quotation product data
         * @returns {*}
         */
        getQuotationProductsFromData: function () {
            return getData().quotation_product_data;
        },

        /**
         * Set quotation customer data
         * @param data
         */
        setQuotationCustomerDataFromData: function (data) {
            var obj = getData();
            obj.quotationCustomerData = data;
            saveData(obj);
        },

        /**
         * Get quotation customer data
         * @returns {*}
         */
        getQuotationCustomerDataFromData: function () {
            return getData().quotationCustomerData;
        }
    }
});
