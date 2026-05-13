/**
 * Copyright © 2017 Vnecoms. All rights reserved.
 * See LICENSE.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'uiRegistry',
    'mageUtils',
], function ($, Component, registry, utils) {
    'use strict';

    return Component.extend({
        defaults: {
            minSearchLength: 2,
            sellerListPaginationElement: "#seller-list-ajax",
            sellerSearchFormSelector: "#seller_search_form"
        },

        initialize: function () {
            this._super();
            utils.limit(this, 'load', this.searchDelay); // execute 'load' after delay

            $(this.inputSelector)
                .unbind('input') // unbind all magento events
                .on('input', $.proxy(this.load, this)) // bind searchsuiteautocomplete load event
                .on('input', $.proxy(this.searchButtonStatus, this)); // bind show/hide search button event
                //.on('focus', $.proxy(this.showContent, this)); // bind show popup event
            $(document).on('click', $.proxy(this.hideContent, this)); // bind hide popup event

            $(document).ready($.proxy(this.load, this)); // load autocomplete data on catalogsearch page after submit search button
            $(document).ready($.proxy(this.searchButtonStatus, this));

        },

        load: function (event) {
            var self = this;
            var searchText = $(self.inputSelector).val();

            if (searchText.length < self.minSearchLength) {
                return false;
            }

            registry.get('searchDataProvider', function (dataProvider) {
                //console.log('loading with ' + searchText);
                dataProvider.searchText = searchText;
                dataProvider.load();
            });
        },

        showContent: function (event) {
            var self = this,
                searchField = $(self.inputSelector),
                searchFieldHasFocus = searchField.is(':focus') && searchField.val().length >= self.minSearchLength;

            this.hideSellerListPagination();
            registry.get('seller_search_form_ajax', function (autocomplete) {
                autocomplete.showContent(searchFieldHasFocus);
            });
        },

        hideContent: function (event) {
            this.showSellerListPagination();
            if ($(this.searchFormSelector).has($(event.target)).length <= 0) {
                registry.get('seller_search_form_ajax', function (autocomplete) {
                    autocomplete.showContent(false);
                });
            }
        },

        showSellerListPagination: function() {
            var self = this;
            $(self.sellerListPaginationElement).show();
        },

        hideSellerListPagination: function() {
            var self = this;
            $(self.sellerListPaginationElement).hide();
        },

        searchButtonStatus: function (event) {
            var self = this,
                searchField = $(self.inputSelector),
                searchButton = $(self.searchButtonSelector),
                searchButtonDisabled = (searchField.val().length > 0) ? false : true;

            searchButton.attr('disabled', searchButtonDisabled);
        },

        loaderShow: function () {
            var self = this;
            var spinner = $(".seller-loading-mask");
            spinner.show();
        },

        loaderHide: function () {
            var self = this;
            var spinner = $(".seller-loading-mask");
            spinner.hide();
        },

        spinnerShow: function () {
            var self = this;
            var spinner = $(self.sellerSearchFormSelector);
            spinner.addClass('loading');
        },

        spinnerHide: function () {
            var self = this;
            var spinner = $(self.sellerSearchFormSelector);
            spinner.removeClass('loading');
        }

    });
});
