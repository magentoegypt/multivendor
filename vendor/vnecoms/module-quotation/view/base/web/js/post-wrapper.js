/*
 * Copyright © 2017 Vnecoms. All rights reserved.
 */

define([
    'jquery',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function ($, confirm) {
    'use strict';

    /**
     * @param {String} url
     * @returns {Object}
     */
    function getForm(url) {
        return $('<form>', {
            'action': url,
            'method': 'POST'
        }).append($('<input>', {
            'name': 'form_key',
            'value': window.FORM_KEY,
            'type': 'hidden'
        }));
    }

    $('#quote-view-cancel-button').click(function () {
        var msg = $.mage.__('Are you sure you want to cancel this order?'),
            url = $('#quote-view-cancel-button').data('url');

        confirm({
            'content': msg,
            'actions': {

                /**
                 * 'Confirm' action handler.
                 */
                confirm: function () {
                    getForm(url).appendTo('body').submit();
                }
            }
        });

        return false;
    });

    $('#quote-view-hold-button').click(function () {
        var url = $('#quote-view-hold-button').data('url');

        getForm(url).appendTo('body').submit();
    });

    $('#quote-view-unhold-button').click(function () {
        var url = $('#quote-view-unhold-button').data('url');

        getForm(url).appendTo('body').submit();
    });
});
