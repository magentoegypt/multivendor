/*
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

define([
    'underscore',
    'jquery',
    'mage/translate',
    'uiComponent',
    'Vnecoms_Quotation/js/model/history',
    'shorten',
], function (_,$, $t, uiComponent, History) {
    'use strict';

    return uiComponent.extend({
        defaults: {
            template: 'Vnecoms_Quotation/history',
            visible: true,
        },

        messages : History.messages,

        /**
         * Calls 'initObservable' of parent
         *
         * @returns {Object} Chainable.
         */
        initObservable: function () {
            this._super();

            return this;
        },

        initialize: function() {
            this._super();
        },


        sortItems: function(itemOne, itemTwo) {
            History.sortItems(itemOne, itemTwo);
        },
        
        readMore: function (element) {
            $(element).shorten({
                showChars: 250,
            });
        }
    });
});