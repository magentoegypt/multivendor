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
            headerTmpl: 'Vnecoms_RMA/grid/columns/order',
            bodyTmpl: 'Vnecoms_RMA/grid/cells/order',
        },
        /**
         * Initializes column component.
         *
         * @returns {Column} Chainable.
         */
        initialize: function () {
            this._super();
            return this;
        }
    });
});
