/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract'
], function (_,registry, Acstract) {
    'use strict';
    return Acstract.extend({
        defaults: {
            visible: false,
            labelVisible: true,
            required: true,
            imports: {
                setItemFieldValue: '${ $.parentName }.order_incremental_id:items'
            }
        },

        /**
         * Initializes component, invokes initialize method of Abstract class.
         *
         *  @returns {Object} Chainable.
         */
        initialize: function () {
            this._super()
                .setInitialValue()
                ._setClasses()
                .initSwitcher();

            return this;
        },

        setItemFieldValue : function(items){
            this.value(items);
        }
    });

});