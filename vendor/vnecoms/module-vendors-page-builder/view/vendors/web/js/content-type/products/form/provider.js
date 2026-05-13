/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Vnecoms_VendorsPageBuilder/js/form/provider',
    'Vnecoms_VendorsPageBuilder/js/form/provider/conditions-data-processor'
], function (Provider, conditionsDataProcessor) {
    'use strict';

    return Provider.extend({
        /** @inheritdoc **/
        save: function () {
            var data = this.get('data');

            conditionsDataProcessor(data, data['condition_option'] + '_source');

            return this._super();
        }
    });
});
