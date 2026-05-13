/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'ko',
    'mage/url'
], function ($, Component, ko, urlBuilder) {
    'use strict';


    return Component.extend({

        defaults: {
            template: 'Vnecoms_VendorsSellerList/seller-search-result',
            addToCartFormSelector: '[data-role=searchsuiteautocomplete-tocart-form]',
            showContent: ko.observable(false),
            result: {
               /* suggest: {
                    data: ko.observableArray([])
                },*/
                seller: {
                    data: ko.observableArray([]),
                    size: ko.observable(0),
                    url: ko.observable('')
                }
            },
            anyResultCount: false,
        },


        initialize: function () {
            var self = this;
            this._super();

            this.anyResultCount = ko.computed(function () {
                var sum = self.result.seller.data().length;
                if (sum > 0) {
                    return true; }
                return false;
            }, this);
        },

        getLoaderImage: function () {
            return this.loadImage;
        }

    });
});
