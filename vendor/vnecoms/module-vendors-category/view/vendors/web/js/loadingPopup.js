define('vesLoadingPopup', [
    'jquery',
    'jquery/ui'
], function ($) {
    'use strict';

    $.widget('ves.loadingPopup', {
        options: {
            message: 'Please wait...',
            timeout: 5000,
            timeoutId: null,
            callback: null,
            template: null
        },

        _create: function () {
            this.template =
                '<div class="popup popup-loading">' +
                '<div class="popup-inner">' + this.options.message + '</div>' +
                '</div>';

            this.popup = $(this.template);

            this._show();
            this._events();
        },

        _events: function () {
            var self = this;

            this.element
                .on('showLoadingPopup', function () {
                    self._show();
                })
                .on('hideLoadingPopup', function () {
                    self._hide();
                });
        },

        _show: function () {
            var options = this.options,
                timeout = options.timeout;

            $('body').trigger('processStart');

            if (timeout) {
                options.timeoutId = setTimeout(this._delayedHide.bind(this), timeout);
            }
        },

        _hide: function () {
            $('body').trigger('processStop');
        },

        _delayedHide: function () {
            this._hide();

            this.options.callback && this.options.callback();

            this.options.timeoutId && clearTimeout(this.options.timeoutId);
        }
    });

    return $.ves.loadingPopup;
});
