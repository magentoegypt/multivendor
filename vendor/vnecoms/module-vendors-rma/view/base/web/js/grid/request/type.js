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
            headerTmpl: 'Vnecoms_RMA/grid/columns/type',
            bodyTmpl: 'Vnecoms_RMA/grid/cells/type',
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
        getStyleClass: function (record){
            var value = record[this.index];
            var class_name = null;
            switch(value) {
                case 'replace':
                    class_name = "type type-replace";
                    break;
                case 'refund':
                    class_name = "type type-refund";
                    break;
            }
            return class_name;
        },
        getFieldClass : function(record){
            return "data-grid-th-type";
        }
    });
});
