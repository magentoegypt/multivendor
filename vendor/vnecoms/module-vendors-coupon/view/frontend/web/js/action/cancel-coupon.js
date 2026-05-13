/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Customer store credit(balance) application
 */
/*global define,alert*/
define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Vnecoms_VendorsCoupon/js/model/resource-url-manager',
        'Magento_Checkout/js/model/error-processor',
        'Magento_SalesRule/js/model/payment/discount-messages',
        'mage/storage',
        'Magento_Checkout/js/action/get-payment-information',
        'Vnecoms_VendorsCoupon/js/action/get-discount-detail',
        'Magento_Checkout/js/model/totals',
        'mage/translate',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, quote, urlManager, errorProcessor, messageContainer, storage, getPaymentInformationAction,getDiscountDetail, totals, $t,
              fullScreenLoader) {
        'use strict';

        return function (couponCode) {
            var quoteId = quote.getQuoteId(),
                url = urlManager.getCancelCouponUrl(couponCode, quoteId),
                message = $t('Your coupon was successfully removed.');

            messageContainer.clear();
            fullScreenLoader.startLoader();

            return storage.delete(
                url,
                false
            ).done(
                function () {
                    var deferred = $.Deferred();
                    
                    getDiscountDetail();
                    
                    totals.isLoading(true);
                    getPaymentInformationAction(deferred);
                    $.when(deferred).done(function () {
                        totals.isLoading(false);
                        fullScreenLoader.stopLoader();
                    });
                    messageContainer.addSuccessMessage({
                        'message': message
                    });
                }
            ).fail(
                function (response) {
                    totals.isLoading(false);
                    fullScreenLoader.stopLoader();
                    errorProcessor.process(response, messageContainer);
                }
            );
        };
    }
);
