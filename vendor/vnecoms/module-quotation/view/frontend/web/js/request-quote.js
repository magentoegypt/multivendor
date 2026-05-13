/**
 * Copyright © 2017 Vnecoms, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
define([
    "jquery",
    "jquery/ui",
    "Magento_Ui/js/modal/alert",
    "Magento_Checkout/js/model/full-screen-loader"
], function($, alert){
    "use strict";
    
    $.widget('ves.quotationRequest', {
        options: {
            updateQuoteActionContainer: '#update_quote_action_container'
        },

        /**
         * Constructor
         *
         * @private
         */
        _create: function() {
            this._super();
            /*this.bindInputCheck();*/
           /* var items = $.find("[data-role='quote-item-qty']");

            for (var i = 0; i < items.length; i++) {
                $(items[i]).on('keypress', $.proxy(function(event) {
                    var keyCode = (event.keyCode ? event.keyCode : event.which);
                    if (keyCode == 13) {
                        event.stopPropagation();
                        //$(this.options.emptyQuoteButton).attr('name', 'update_quote_action_temp');
                        //$(this.options.updateQuoteActionContainer).attr('name', 'update_quote_action').attr('value', 'update_qty');
                        this._updateQuoteItem();
                    }
                }, this));
            }*/
        }
    });

    return $.ves.quotationRequest;
});
