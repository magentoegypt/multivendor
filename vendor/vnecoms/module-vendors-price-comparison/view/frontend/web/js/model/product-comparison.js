/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'ko'
], function (ko) {
    'use strict';

    var products = ko.observableArray([]);

    return {
        isLoading: ko.observable(false),

        /**
         * Set Products
         *
         * @param {*} productsData
         */
        setProducts: function (productsData) {
            products(productsData);
        },

        /**
         * Get shipping rates
         *
         * @returns {*}
         */
        getProducts: function () {
            return products;
        }
    };
});
