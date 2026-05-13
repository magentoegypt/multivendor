/**
 * Custom tooltip initializer
 */
define([
    'jquery',
    'uiElement',
    'Amasty_CheckoutCore/vendor/tooltipster/js/tooltipster.min'
], function (
    $,
    Element
) {
    'use strict';

    return Element.extend({
        defaults: {
            selectors: {
                tooltipElement: '[data-amcheckout-js="tooltip"]'
            }
        },

        initialize: function () {
            this._super();

            this.initTooltip();

            return this;
        },

        initTooltip: function () {
            $.async(this.selectors.tooltipElement, function (element) {
                $(element).tooltipster({
                    position: 'right',
                    contentAsHtml: true,
                    interactive: true,
                    trigger: 'click',
                    theme: 'amcheckout-default-tooltip'
                });
            });
        },

        isTouchDevice: function () {
            return ('ontouchstart' in window)
                || (navigator.maxTouchPoints > 0)
                || (navigator.msMaxTouchPoints > 0);
        }
    });
});
