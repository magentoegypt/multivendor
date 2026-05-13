/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'Vnecoms_VendorsPageBuilder/js/form/element/visual-select'
], function (_, Select) {
    'use strict';

    return Select.extend({
        defaults: {
            visible: false
        },

        /**
         * Dependently display dropdown component if it contains more than one option
         *
         * @returns {Object} Chainable
         */
        setOptions: function () {
            this._super();

            if (this.options().length > 1) {
                this.setVisible(true);
            }

            return this;
        }
    });
});
