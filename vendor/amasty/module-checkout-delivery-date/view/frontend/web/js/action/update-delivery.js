/**
 * Update Delivery Date Action
 */
define([
    'Amasty_CheckoutCore/js/model/resource-url-manager',
    'Amasty_CheckoutDeliveryDate/js/model/delivery',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'Magento_Checkout/js/model/error-processor'
], function (resourceUrlManager, deliveryService, quote, storage, errorProcessor) {
    'use strict';

    return function (payload) {
        var serviceUrl;

        if (deliveryService.isLoading()) {
            return;
        }
        payload.cartId = quote.getQuoteId();

        serviceUrl = resourceUrlManager.getUrlForDelivery(quote);

        storage.post(
            serviceUrl, JSON.stringify(payload), false
        ).fail(
            function (response) {
                errorProcessor.process(response);
            }
        ).always(
            function () {
                deliveryService.isLoading(false);
            }
        );
    };
});
