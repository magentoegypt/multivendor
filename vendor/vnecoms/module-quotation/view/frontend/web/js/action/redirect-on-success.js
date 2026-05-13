/*
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

define(
    [
        'Vnecoms_Quotation/js/model/resource-url-manager'
    ],
    function (resourceUrlManager) {
        'use strict';

        return {
            /**
             * Provide redirect to page
             */
            execute: function (quoteId) {
                var url = resourceUrlManager.getUrlForRedirectOnSuccess(quoteId, {id: quoteId});
                window.location.replace(url);
            }
        };
    }
);
