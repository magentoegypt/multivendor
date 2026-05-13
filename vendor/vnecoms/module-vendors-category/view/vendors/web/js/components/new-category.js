/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Catalog/js/components/new-category'
], function (Select) {
    'use strict';

    return Select.extend({

        /**
         * Parse data and set it to options.
         *
         * @param {Object} data - Response data object.
         * @returns {Object}
         */
        setParsed: function (data) {
            var option = this.parseData(data);

            if (data.error) {
                return this;
            }


          //  this.options([]);
            this.setOption(option);
            this.set('newOption', option);
        },

        /**
         * Normalize option object.
         *
         * @param {Object} data - Option object.
         * @returns {Object}
         */
        parseData: function (data) {
           // console.log(data.category);
            return {
                'is_active': data.category['is_active'],
                level: data.category.level,
                value: data.category['category_id'],
                label: data.category.name,
                parent: data.category.parent_id
            };
        },

        /**
         * Check selected option
         *
         * @param {String} value - option value
         * @return {Boolean}
         */
        isShowBox: function (level) {
            level = parseInt(level); console.log(level);
            if (level > 0) {
return 'display:block;'; }
            return 'display:none;';
        }
    });
});
