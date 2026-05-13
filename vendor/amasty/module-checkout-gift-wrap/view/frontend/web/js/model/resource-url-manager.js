/*jshint browser:true jquery:true*/
/*global alert*/

define([
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/url-builder',
    'mageUtils'
], function (customer, urlBuilder, utils) {
    "use strict";

    return {
        /**
         * @param {Object} quote
         *
         * @returns {string|*}
         */
        getUrlForGiftWrap: function (quote) {
            var params = (this.getCheckoutMethod() === 'guest') ? { cartId: quote.getQuoteId() } : {},
                urls = {
                    'guest': '/amasty_checkout/guest-carts/:cartId/gift-wrap',
                    'customer': '/amasty_checkout/carts/mine/gift-wrap'
                };

            return this.getUrl(urls, params);
        },

        /**
         * @param {Object} quote
         *
         * @returns {string|*}
         */
        getUrlForGiftMessage: function (quote) {
            var params = (this.getCheckoutMethod() === 'guest') ? { cartId: quote.getQuoteId() } : {},
                urls = {
                'guest': '/amasty_checkout/guest-carts/:cartId/gift-message',
                'customer': '/amasty_checkout/carts/mine/gift-message'
            };

            return this.getUrl(urls, params);
        },

        /**
         * Get url for service
         *
         * @param {Array} urls
         * @param {Object} urlParams
         *
         * @returns {string|*}
         * */
        getUrl: function (urls, urlParams) {
            var url;

            if (utils.isEmpty(urls)) {
                return 'Provided service call does not exist.';
            }

            if (!utils.isEmpty(urls['default'])) {
                url = urls['default'];
            } else {
                url = urls[this.getCheckoutMethod()];
            }

            return urlBuilder.createUrl(url, urlParams);
        },

        /**
         * @returns {string}
         */
        getCheckoutMethod: function () {
            return customer.isLoggedIn() ? 'customer' : 'guest';
        }
    };
}
);
