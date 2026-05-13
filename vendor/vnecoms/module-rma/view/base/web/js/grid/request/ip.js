/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'Magento_Ui/js/grid/columns/column'
], function (_, Column) {
    'use strict';

    return Column.extend({
        defaults: {
            headerTmpl: 'Vnecoms_RMA/grid/columns/ip',
            bodyTmpl: 'Vnecoms_RMA/grid/cells/ip',
        },
        /**
         * Initializes column component.
         *
         * @returns {Column} Chainable.
         */
        initialize: function () {
            this._super();
            return this;
        },
        getFlag: function (record) {
            return record["geo_fag"];
        },
        getCountry: function (record) {
            return record["geo_country"];
        },
        getStyleClass: function (record) {
            return "flag-ip";
        },
        getFlagClass: function (record) {
            return null;
        },
    });
});
