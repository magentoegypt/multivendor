/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'uiRegistry',
    'underscore'
], function ($, Component, registry, _) {
    'use strict';

    $.Seller = function (data) {
        //console.log(data);
        this.vendor_id = data.vendor_id;
        this.image = data.image;
        //this.reviews_rating = data.reviews_rating;
        //this.short_description = data.short_description;
        //this.description = data.description;
        this.seller_url = data.seller_url;
        this.product_counts = data.product_counts;
        this.seller_item_urls = data.seller_item_urls;
    };

    return Component.extend({
        defaults: {
            localStorage: $.initNamespaceStorage('seller-search-storge').localStorage,
            searchText: ''
        },

        initialize: function () {
            this._super();
        },

        load: function () {
            var self = this;
            //console.log('before call ajax with query ' + this.searchText);
            //console.log(this.url);
            if (this.xhr) {
                this.xhr.abort();
            }

            this.xhr = $.ajax({
                method: "get",
                dataType: "json",
                url: this.url,
                data: {seller_query: this.searchText},
                beforeSend: function () {
                    self.spinnerShow();
                    if (self.loadFromLocalStorage(self.searchText)) {
                        self.showContent();
                    }
                },
                success: $.proxy(function (response) {
                    self.spinnerHide();
                    //console.log(response);
                    self.parseData(response);
                    self.saveToLocalStorage(response, self.searchText);
                    self.showContent();
                })
            });
        },

        showContent: function () {
            registry.get('searchBindEvents', function (binder) {
                binder.showContent();
            });
        },

        spinnerShow: function () {
            registry.get('searchBindEvents', function (binder) {
                //binder.spinnerShow();
                binder.loaderShow();
            });
        },

        spinnerHide: function () {
            registry.get('searchBindEvents', function (binder) {
                //binder.spinnerHide();
                binder.loaderHide();
            });
        },

        parseData: function (response) {
            //this.setSuggested(this.getResponseData(response, 'suggest'));
            this.setSellers(this.getResponseData(response, 'seller'));
        },

        getResponseData: function (response, code) {
            var data = [];

            if (_.isUndefined(response.result)) {
                return data;
            }

            $.each(response.result, function (index, obj) {
                if (obj.code == code) {
                    data = obj;
                }
            });

            return data;
        },

        setSellers: function (sellersData) {
            var sellers = [];

            if (!_.isUndefined(sellersData.data)) {
                sellers = $.map(sellersData.data, function (seller) {
                    return new $.Seller(seller) });
            }

            registry.get('seller_search_form_ajax', function (autocomplete) {
                autocomplete.result.seller.data(sellers);
                autocomplete.result.seller.size(sellersData.size);
                //autocomplete.result.seller.seller_url(sellersData.seller_url);
            });
        },

        loadFromLocalStorage: function (queryText) {
            if (!this.localStorage) {
                return; }

            var hash = this._hash(queryText);
            var data = this.localStorage.get(hash);

            if (!data) {
                return false; }

            this.parseData(data);

            return true;
        },

        saveToLocalStorage: function (data, queryText) {
            if (!this.localStorage) {
                return; }

            var hash = this._hash(queryText);

            this.localStorage.remove(hash);
            this.localStorage.set(hash, data);
        },

        _hash: function (object) {
            var string = JSON.stringify(object) + "";

            var hash = 0, i, chr, len;
            if (string.length == 0) {
                return hash;
            }
            for (i = 0, len = string.length; i < len; i++) {
                chr = string.charCodeAt(i);
                hash = ((hash << 5) - hash) + chr;
                hash |= 0;
            }
            return 'h' + hash;
        }

    });
});
