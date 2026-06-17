/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Vnecoms_VendorsCoupon/js/model/resource-url-manager',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Vnecoms_VendorsCoupon/js/model/discount'
    ],
    function ($, quote, urlManager, storage, errorProcessor, customer, discountModel) {
        'use strict';

        return function (deferred, messageContainer) {
            var quoteId = quote.getQuoteId(),
            serviceUrl = urlManager.getDiscountDetailUrl(quoteId),

            deferred = deferred || $.Deferred();

            return storage.get(
                serviceUrl, false
            ).done(
                function (response) {
                    // Resilient parse: endpoint may return empty/non-JSON (e.g. no vendor coupon).
                    // Must not throw, or the deferred never resolves and checkout hangs on the spinner.
                    var detail = {};
                    try {
                        if (response && typeof response === 'string') {
                            detail = $.parseJSON(response);
                        } else if (response && typeof response === 'object') {
                            detail = response;
                        }
                    } catch (e) {
                        detail = {};
                    }
                    discountModel.setDiscountDetail(detail);
                    deferred.resolve();
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response, messageContainer);
                    deferred.reject();
                }
            );
        };
    }
);
