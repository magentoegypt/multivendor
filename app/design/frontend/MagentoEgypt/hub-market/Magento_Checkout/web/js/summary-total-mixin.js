/**
 * Hub Market — show the Order Summary totals from step one.
 *
 * Core's abstract-total gates every summary row behind
 * `stepNavigator.isProcessed('shipping')`, so on step one the summary shows the
 * cart lines and nothing else — no Subtotal, no Shipping, no Total.
 *
 * The reference shows the full summary on all three steps, and so did this
 * store until Amasty's own version of this override was switched off (see the
 * mixin block in the theme's requirejs-config.js for why that had to happen).
 * This is that one behaviour, kept, without pulling the rest of Amasty's
 * checkout back in.
 */
define([], function () {
    'use strict';

    return function (Component) {
        return Component.extend({
            /** Totals are worth showing as soon as there are totals. */
            isFullMode: function () {
                return !!this.getTotals();
            }
        });
    };
});
