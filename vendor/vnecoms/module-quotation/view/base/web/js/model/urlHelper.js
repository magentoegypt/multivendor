/*
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

define(
    [
        'mage/url',
        'mageUtils'
    ],
    function(url, utils) {
        "use strict";

        /**
         * A model for handling the action URL's
         */
        return {

            /**
             * Get the update quote session URL.
             * @param quote
             * @returns string
             * @param params
             */
            getUrlForRedirectOnSuccess: function(quote, params) {
                var urls = {
                    'guest': 'quotation/quote/success',
                    'customer': 'quotation/quote/success'
                };
                return this.getUrl(urls, params, false);
            },

            /**
             * Get the update quote session URL.
             * @param quote
             * @returns string
             * @param params
             */
            getUrlForUpdateQuote: function(quote, params) {
                var urls = {
                    'guest': 'quotation/quote_ajax/updateQuote',
                    'customer': 'quotation/quote_ajax/updateQuote'
                };
                return this.getUrl(urls, params, false);
            },

            /**
             * Get url for service
             * @return string
             */
            getUrl: function(urls, urlParams) {
                var newUrl;

                if (utils.isEmpty(urls)) {
                    return 'Provided service call does not exist.';
                }

                if (!utils.isEmpty(urls['default'])) {
                    newUrl = urls['default'];
                } else {
                    newUrl = urls[this.getCheckoutMethod()];
                }

                return url.build(newUrl) + this.prepareParams(urlParams);
            },

            /**
             * Format params
             *
             * @param {Object} params
             * @returns {string}
             */
            prepareParams: function(params) {
                var result = '?';

                _.each(params, function (value, key) {
                    result += key + '=' + value + '&';
                });

                return result.slice(0, -1);
            }
        };
    }
);
