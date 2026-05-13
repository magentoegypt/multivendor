/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'Magento_Ui/js/grid/listing'
], function (_, Column) {
    'use strict';

    return Column.extend({

        defaults: {
            template: 'Vnecoms_RMA/grid/listing'
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
        /**
         * get Row Class
         */
        getRowClass: function (index,row) {
            var className = "";
            var isAdminRead = row["is_admin_read"];
            if (isAdminRead == 0) {
className = "is_not_read"
            if (index % 2) {
className += ' _odd-row'; } }
            return className;
        }



    });
});
