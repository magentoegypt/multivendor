/**
 * Copyright © 2017 Vnecoms, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true, jquery:true*/
define([
    "jquery",
    "jquery/ui"
], function($){
    "use strict";

    $.widget('ves.quotesSearch', {
        options: {
            telephone: '#quotation-telephone', /* Search by telephone. */
            emailAddress: '#quotation-email', /* Search by email address. */
            searchType: '#quote-search-type-id' /* Search element used for choosing between the two. */
        },

        _create: function() {
            $(this.options.searchType).on('change', $.proxy(this._showIdentifyBlock, this)).trigger('change');
        },

        /**
         * Show either the search by zip code option or the search by email address option.
         * @private
         * @param e - Change event. Event target value is either 'zip' or 'email'.
         */
        _showIdentifyBlock: function(e) {
            var value = $(e.target).val();
            if(value === 'customer_phone'){
            	$(this.options.telephone).show();
            	$(this.options.emailAddress).hide();
            }else{
            	$(this.options.telephone).hide();
            	$(this.options.emailAddress).show();
            }
        }
    });

    return $.ves.quotesSearch;
});
