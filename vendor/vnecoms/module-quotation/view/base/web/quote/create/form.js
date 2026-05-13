/*
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

define([
    'jquery',
    'Vnecoms_Quotation/quote/create/scripts'
], function (jQuery) {
    'use strict';

    var $el = jQuery('#edit_form'),
        config,
        baseUrl,
        quote,
        quoteId,
        updateQuoteInfoUrl;

    if (!$el.length || !$el.data('quote-config')) {
        return;
    }

    config = $el.data('quote-config');
    baseUrl = $el.data('load-base-url');
    quoteId = $el.data('quote-id');
    updateQuoteInfoUrl = $el.data('quote-info-url');
    
    quote = new AdminQuote(config);
    quote.setLoadBaseUrl(baseUrl);
    quote.setCurrentQuoteId(quoteId);
    quote.setUpdateQuoteInfoUrl(updateQuoteInfoUrl);

    window.quote = quote;
});
