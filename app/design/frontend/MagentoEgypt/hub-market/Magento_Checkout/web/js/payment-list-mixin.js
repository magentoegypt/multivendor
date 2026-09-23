/**
 * Hub Market — TC54 (14zb93nupaj): guests saw "No Payment Methods" on the
 * first visit to the payment step, and the methods only appeared on a retry.
 *
 * The methods were never missing. shipping-information returned them
 * (checkmo, cashondelivery), but core only counts a method as available once
 * its renderer component has been loaded AND rendered into the group region —
 * and core fetches those renderers lazily, on entering the payment step. On a
 * loaded origin that took 20-40s (origin log 09:13:59 -> 09:14:21 -> 09:14:39),
 * and for the whole wait the template's `ifnot` branch said there were none.
 * The retry "worked" because the files were cached by then.
 *
 *   1. Preload every registered renderer and its template as soon as the
 *      checkout loads, so they are ready long before the shopper gets there.
 *   2. hmIsRendering(): methods exist but have not rendered yet. The list
 *      template shows a spinner for that state and keeps the message for a
 *      quote that genuinely has no method.
 */
define([
    'ko',
    'underscore',
    'Magento_Checkout/js/model/payment/renderer-list',
    'Magento_Checkout/js/model/payment/method-list'
], function (ko, _, rendererList, methodList) {
    'use strict';

    var preloaded = {};

    /**
     * @param {Array} renderers
     */
    function preload(renderers) {
        _.each(renderers, function (renderer) {
            var component = renderer && renderer.component;

            if (!component || preloaded[component]) {
                return;
            }
            preloaded[component] = true;
            require([component], function (Renderer) {
                var defaults = Renderer && (Renderer.defaults || (Renderer.prototype && Renderer.prototype.defaults)),
                    template = defaults && defaults.template;

                if (typeof template === 'string' && template.indexOf('/') > 0) {
                    require(['text!' + template + '.html'], function () {}, function () {});
                }
            }, function () {});
        });
    }

    return function (List) {
        return List.extend({
            /** @inheritdoc */
            initialize: function () {
                this._super();

                preload(rendererList());
                rendererList.subscribe(preload);

                this.hmIsRendering = ko.pureComputed(function () {
                    return methodList().length > 0 && !this.isPaymentMethodsAvailable();
                }, this);

                return this;
            }
        });
    };
});
