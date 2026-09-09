/**
 * Hub Market — the Order Summary must not print a shipping cost it does not
 * have yet (CL036-DEV01.19).
 *
 * REPORTED AS a language bug: Arabic checkout showed EGP 5 shipping and a
 * EGP 30 total, English showed EGP 0 and EGP 25, for the same basket. It is not
 * a language bug. Checked first, because a real per-locale difference would
 * have to be fixed in configuration and not in a template:
 *
 *   carriers/flatrate/active            en '1'      ar '1'
 *   carriers/flatrate/price             en '5.00'   ar '5.00'
 *   carriers/freeshipping/active        en '0'      ar '0'
 *
 * Identical. Shipping config is website-scoped and both store views sit on
 * website 1, so it cannot differ by language. Nor do the two free-shipping cart
 * rules apply: #2 needs base_subtotal >= 50 and the basket was 25, and #6
 * ("Free Shipping Offer", no conditions) ran 2026-06-19 to 2026-06-25 and has
 * expired. EGP 5 is the correct figure. The ENGLISH screenshot is the wrong one
 * — and its own page contradicted it, listing "Flat Rate — Fixed EGP 5" in the
 * methods panel directly below a summary that read EGP 0.
 *
 * WHAT ACTUALLY HAPPENS. Core gates every summary row behind
 * `stepNavigator.isProcessed('shipping')`, so on step one there is no summary
 * to be wrong. This theme deliberately removes that gate — the client wants the
 * totals visible from step one (see summary-total-mixin.js) — which leaves the
 * shipping row rendering `totals()['shipping_amount']` during the window before
 * the quote has been costed with a method. That value is 0, and 0 renders as a
 * price rather than as "not known yet".
 *
 * So the row keeps core's own answer for that window. `isCalculated()` already
 * returns false until a shipping method is set, and core's getValue() already
 * returns "Not yet calculated" in that case — it was only the forced
 * `isFullMode()` that let a zero through. Restoring the check here, and here
 * only, keeps the subtotal, discount and total on screen from step one while
 * the shipping row stays honest.
 */
define(['Magento_Checkout/js/model/step-navigator'], function (stepNavigator) {
    'use strict';

    return function (Component) {
        return Component.extend({
            /**
             * Core's definition, restored for THIS row. The site-wide override
             * in summary-total-mixin.js still applies to every other total.
             *
             * @returns {Boolean}
             */
            isFullMode: function () {
                if (!this.getTotals()) {
                    return false;
                }

                return stepNavigator.isProcessed('shipping');
            }
        });
    };
});
