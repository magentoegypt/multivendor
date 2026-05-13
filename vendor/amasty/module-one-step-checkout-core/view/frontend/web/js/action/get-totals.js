define([
    'jquery',
    'mage/utils/wrapper',
    'underscore',
    'Amasty_CheckoutCore/js/model/resource-url-manager',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/error-processor'
], function ($, wrapper, _, resourceUrlManager, quote, storage, totalsService, errorProcessor) {
    'use strict';

    return function (callbacks, deferred) {
        var serviceUrl = resourceUrlManager.getUrlForTotalsEstimationForNewAddress(quote),
            request,
            payload,
            requiredFields = ['countryId', 'region', 'regionId', 'postcode'],
            address = quote.isVirtual() ? quote.billingAddress() : quote.shippingAddress(),
            deferredObject = deferred || $.Deferred();

        address = _.pick(address, requiredFields);

        payload = {
            addressInformation: {
                address: address
            }
        };

        if (quote.shippingMethod() && quote.shippingMethod()['method_code']) {
            payload.addressInformation['shipping_method_code'] = quote.shippingMethod()['method_code'];
            payload.addressInformation['shipping_carrier_code'] = quote.shippingMethod()['carrier_code'];
        }

        totalsService.isLoading(true);

        request = window.checkoutConfig.isNegotiableQuote ?
            storage.get.bind(this, serviceUrl, false) :
            storage.post.bind(this, serviceUrl, JSON.stringify(payload), false);

        return request().done(function (response) {
            var proceed = true;

            if (callbacks && callbacks.length > 0) {
                _.each(callbacks, function (callback) {
                    proceed = proceed && callback();
                });
            }

            if (proceed) {
                quote.setTotals(response);
                deferredObject.resolve();
            }
        }).fail(function (response) {
            if (response.responseText || response.status) {
                errorProcessor.process(response);
            }

            deferredObject.reject();
        }).always(function () {
            totalsService.isLoading(false);
        });
    };
});
