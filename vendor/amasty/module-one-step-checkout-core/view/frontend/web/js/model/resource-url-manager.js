/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/model/resource-url-manager',
        'mageUtils'
    ],
    function (customer, urlBuilder, resourceUrlManager, utils) {
        "use strict";
        return {
            getUrlForDelivery: function (quote) {
                var params = (this.getCheckoutMethod() == 'guest') ? {cartId: quote.getQuoteId()} : {};
                var urls = {
                    'guest': '/amasty_checkout/guest-carts/:cartId/delivery',
                    'customer': '/amasty_checkout/carts/mine/delivery'
                };

                return this.getUrl(urls, params);
            },

            getUrlForRemoveItem: function (quote) {
                var params = (this.getCheckoutMethod() == 'guest') ? {cartId: quote.getQuoteId()} : {};
                var urls = {
                    'guest': '/amasty_checkout/guest-carts/:cartId/remove-item',
                    'customer': '/amasty_checkout/carts/mine/remove-item'
                };

                return this.getUrl(urls, params);
            },

            getUrlForUpdateItem: function (quote) {
                var params = (this.getCheckoutMethod() == 'guest') ? {cartId: quote.getQuoteId()} : {};
                var urls = {
                    'guest': '/amasty_checkout/guest-carts/:cartId/update-item',
                    'customer': '/amasty_checkout/carts/mine/update-item'
                };

                return this.getUrl(urls, params);
            },

            getUrlForAdditionalFields: function (quote) {
                var params = (this.getCheckoutMethod() == 'guest') ? {cartId: quote.getQuoteId()} : {};
                var urls = {
                    'guest': '/amasty_checkout/guest-carts/:cartId/amcheckoutFields',
                    'customer': '/amasty_checkout/carts/mine/amcheckoutFields'
                };

                return this.getUrl(urls, params);
            },

            getUrlForSavePassword: function (quote) {
                var params = (this.getCheckoutMethod() == 'guest') ? {cartId: quote.getQuoteId()} : {},
                    urls = {
                        'guest': '/amasty_checkout/guest-carts/:cartId/save-password'
                };

                return this.getUrl(urls, params);
            },

            /** Get url for service */
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

            getCheckoutMethod: function () {
                return customer.isLoggedIn() ? 'customer' : 'guest';
            },

            /**
             * Making url for total estimation request.
             *
             * @param {Object} quote - Quote model.
             * @returns {String} Result url.
             */
            getUrlForTotalsEstimationForNewAddress: function (quote) {
                if (window.checkoutConfig.isNegotiableQuote) {
                    var params = {
                            quoteId: quote.getQuoteId()
                        },
                        urls = {
                            'negotiable': '/negotiable-carts/:quoteId/totals/?isNegotiableQuote=true'
                        };

                    return resourceUrlManager.getUrl(urls, params);
                }

                return resourceUrlManager.getUrlForTotalsEstimationForNewAddress(quote);
            }
        };
    }
);
