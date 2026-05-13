/**
 * Copyright © 2013-2017 Vnecoms, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    "jquery",
    "mage/url",
    "jquery/ui",
    "Magento_Theme/js/view/messages",
    "Magento_Catalog/js/product/list/toolbar"

], function($, urlBuilder, ui, messageComponent) {
    "use strict";

    /**
     * SellerListToolbarForm Widget - this widget is setting cookie and submitting form according to toolbar controls
     */
    $.widget('ves.sellerListToolbarForm', $.mage.productListToolbarForm, {

        options: {
            modeControl: '[data-role="mode-switcher"]',
            directionControl: '[data-role="direction-switcher"]',
            orderControl: '[data-role="sorter"]',
            limitControl: '[data-role="limiter"]',
            mode: 'product_list_mode',
            direction: 'product_list_dir',
            order: 'product_list_order',
            sellersListAjaxSelector: '#seller-list-ajax',
            sellersListDefaultSelector: '.seller-wrapper',
            sellerBoxSearchElement: '#sellersearch',
            searchButtonElement: '#seller-search',
            limit: 'seller_list_limit',
            modeDefault: 'grid',
            directionDefault: 'asc',
            orderDefault: 'position',
            limitDefault: '5',
            url: ''
        },

        /**
         * @inheritdoc
         */
        _create: function () {
            var self = this;
            //console.log(this.options.limitDefault);
            //this._bind($(this.options.limitControl), this.options.limit, this.options.limitDefault);
            //self._super();
            this._bind($(this.options.limitControl), this.options.limit, this.options.limitDefault);
            //this.initSellerListUrl();
            this.initObserve();
        },

        initSellerListUrl: function () {
            var self = this;
            $.ves.sellerListToolbarForm.prototype.changeUrl = function (paramName, paramValue, defaultValue) {
                var decode = window.decodeURIComponent;
                var urlPaths = this.options.url.split('?'),
                    baseUrl = urlPaths[0],
                    urlParams = urlPaths[1] ? urlPaths[1].split('&') : [],
                    paramData = {},
                    parameters;
                for (var i = 0; i < urlParams.length; i++) {
                    parameters = urlParams[i].split('=');
                    paramData[decode(parameters[0])] = parameters[1] !== undefined
                        ? window.decodeURIComponent(parameters[1].replace(/\+/g, '%20'))
                        : '';
                }
                paramData[paramName] = paramValue;
                if (paramValue == defaultValue) {
                    delete paramData[paramName];
                }
                paramData = $.param(paramData);
                location.href = baseUrl + (paramData.length ? '?' + paramData : '');
                self.ajaxSubmit(location.href);
            }
        },

        initObserve: function () {
            var self = this;

            $(".pages-items li a").off('click').on('click', function (e) {
                var link = self.checkUrl($(this).prop('href'));
                if(!link) return;

                self.ajaxSubmit(link);
                e.stopPropagation();
                e.preventDefault();
            });

        },

        checkUrl: function (url) {
            var regex = /(http|https):\/\/(\w+:{0,1}\w*)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%!\-\/]))?/;

            return regex.test(url) ? url : null;
        },


        /**
         * Ajax for pagination
         * @param submitUrl
         */
        ajaxSubmit: function (submitUrl) {
            var self = this;
            //console.log(submitUrl);
            $.ajax({
                url: submitUrl,
                data: {isAjax: 1},
                type: 'post',
                dataType: 'json',
                beforeSend: function () {
                    $('#ves_sellerlist_overlay').show();
                    if (typeof window.history.pushState === 'function') {
                        window.history.pushState({url: submitUrl}, '', submitUrl);
                    }
                },
                success: function (res) {
                    //console.log(res);
                    if (res.backUrl) {
                        window.location = res.backUrl;
                        return;
                    }
                    if (res.sellers) {
                        $(self.options.sellersListDefaultSelector).replaceWith(res.sellers);
                        $(self.options.sellersListDefaultSelector).trigger('contentUpdated');
                    }

                    $('#ves_sellerlist_overlay').hide();
                },
                error: function () {
                    window.location.reload();
                }
            });
        },

    });

    return $.ves.sellerListToolbarForm;
});
