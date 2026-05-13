/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'mage/storage',
    'Magento_Ui/js/model/messageList',
    'Vnecoms_VendorsPriceComparison/js/model/product-comparison',
    'mage/translate'
], function ($, storage, globalMessageList, productComparison, $t) {
    'use strict';

    var callbacks = [],

    /**
     * @param {Object} productData
     */
    action = function (productData, deferred) {

        deferred = deferred || $.Deferred();

        return storage.post(
            'pricecomparison/ajax/loadProduct?isAjax=true',
            JSON.stringify(productData)
        ).done(function (response) {
            if (response.error) {
              globalMessageList.addErrorMessage({
                  'message': response.msg
              });
            } else {
                productComparison.setProducts(response.products);
            }
            deferred.resolve();
        }).fail(function () {
            globalMessageList.addErrorMessage({
                'message': $t('Could not load comparison product. Please try again')
            });
            deferred.reject();
        });
    };

    return action;
});
