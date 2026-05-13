/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'Magento_Ui/js/grid/columns/select'
], function (_, Column) {
    'use strict';

    return Column.extend({

        defaults: {
            headerTmpl: 'Vnecoms_RMA/grid/columns/status',
            bodyTmpl: 'Vnecoms_RMA/grid/cells/status',
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

        getStyleClass: function (record) {
            var value = record["code"];
            var class_name = null;
            switch (value) {
                case 'pending':
                    class_name = "status status-pending";
                    break;
                case 'approval':
                    class_name = "status status-approval";
                    break;
                case 'package_sent':
                    class_name = "status status-package_sent";
                    break;
                case 'package_received':
                    class_name = "status status-package_received";
                    break;
                case 'canceled':
                    class_name = "status status-canceled";
                    break;
                case 'package_returned':
                    class_name = "status status-package_returned";
                    break;
                case 'resolved':
                    class_name = "status status-resolved";
                    break;
            }
            return class_name;
        }
    });
});
