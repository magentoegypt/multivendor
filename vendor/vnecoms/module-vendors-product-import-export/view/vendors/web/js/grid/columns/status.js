/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'underscore',
    'jquery',
    'Magento_Ui/js/grid/columns/column',
    'Magento_Ui/js/modal/alert',
    'mage/translate',
], function (_, $, Column, uiAlert, $t) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'ui/grid/cells/html'
        },
        
        /*eslint-disable eqeqeq*/
        /**
         * Retrieves label associated with a provided value.
         *
         * @returns {String}
         */
        getLabel: function (record) {
            var options = this.options || [],
                values = this._super(),
                label = [];

            if (!Array.isArray(values)) {
                values = [values];
            }

            values = values.map(function (value) {
                return value + '';
            });

            options.forEach(function (item) {
                if (_.contains(values, item.value + '')) {
                    label.push(item.label);
                }
            });

            return label.join(', ');
        },
        
        getErrorMessage: function (record) {
            var value = record[this.index];
            if (value != 3) {
return; }
            try {
                var errors = $.parseJSON(record.error_msg);
                if (errors && $.isArray(errors)) {
                    uiAlert({
                        title: $t('Error'),
                        content: errors.join('<br />')
                    });
                }
            } catch (e) {
                uiAlert({
                    title: $t('Error'),
                    content: record.error_msg
                });
            }
        },
        /**
         * Get label class
         *
         */
        getlabelClass: function (record) {
            var value = record[this.index];
            var statusClass = ['label'];
            if (value == 0) {
                statusClass.push('label-default');
                statusClass.push('import-status-draft');
            } else if (value == 1) {
                statusClass.push('label-warning');
                statusClass.push('import-status-draft-in-process');
            } else if (value == 2) {
                statusClass.push('label-success');
                statusClass.push('import-status-processing');
            } else if (value == 3) {
                statusClass.push('label-danger');
                statusClass.push('import-status-error');
            }
            
            return statusClass.join(" ");
        }

        /*eslint-enable eqeqeq*/
    });
});
