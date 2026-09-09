/**
 * Hub Market — the mini-cart's free-shipping strip (CL036-DEV01.14).
 *
 * The markup lives in this theme's copy of Magento_Checkout/minicart/content;
 * this fills it in. It is deliberately NOT a Knockout binding inside that
 * template: the threshold is a server value and the subtotal is a customer-data
 * value, and expressing "one of these comes from PHP and the other from the
 * section store" inside a KO expression is far harder to read than this.
 *
 * `customer-data` is the same section store the rest of the mini-cart reads, so
 * this updates on exactly the same events — add to cart, quantity change,
 * removal — with no request of its own.
 *
 * Everything here is progressive enhancement. If the script never loads, or the
 * store has no free-shipping rule, the strip stays `hidden` and the mini-cart is
 * simply the mini-cart.
 */
define([
    'Magento_Customer/js/customer-data',
    'Magento_Catalog/js/price-utils',
    'mage/translate'
], function (customerData, priceUtils, $t) {
    'use strict';

    return function (config) {
        var threshold = Number(config.threshold) || 0,
            priceFormat = config.priceFormat || {},
            cart;

        if (threshold <= 0) {
            //  No unprompted free-shipping rule on this store. Nothing to
            //  promise, so nothing to draw.
            return;
        }

        cart = customerData.get('cart');

        /**
         * @param {Number} subtotal
         * @returns {void}
         */
        function paint(subtotal) {
            //  The strip is inside the Knockout-rendered dropdown, so it is
            //  re-created whenever the cart changes. Look it up every time
            //  rather than holding a reference to a node that may be dead.
            var box = document.querySelector('[data-role="hm-minicart-freeship"]'),
                text,
                fill,
                remaining,
                pct;

            if (!box) {
                return;
            }

            text = box.querySelector('[data-role="hm-minicart-freeship-text"]');
            fill = box.querySelector('[data-role="hm-minicart-freeship-fill"]');
            remaining = threshold - subtotal;

            //  Nothing in the basket yet: the strip would read as a demand
            //  rather than an offer, and there is a "your cart is empty"
            //  message directly under it.
            if (!(subtotal > 0)) {
                box.hidden = true;

                return;
            }

            box.hidden = false;

            if (remaining <= 0) {
                box.classList.add('hm-minicart__ship--won');
                text.textContent = $t('You qualify for free shipping');
                fill.style.inlineSize = '100%';

                return;
            }

            box.classList.remove('hm-minicart__ship--won');
            //  "Add EGP 42 more for free shipping" — one string with the amount
            //  interpolated, so a translator can move the amount within it.
            text.textContent = $t('Add %1 more for free shipping')
                .replace('%1', priceUtils.formatPrice(remaining, priceFormat));

            pct = Math.max(0, Math.min(100, (subtotal / threshold) * 100));
            fill.style.inlineSize = pct.toFixed(2) + '%';
        }

        /**
         * @returns {void}
         */
        function update() {
            paint(Number((cart() || {}).subtotalAmount) || 0);
        }

        cart.subscribe(update);
        update();

        //  The dropdown's contents are rendered by Knockout when it first
        //  opens, so on a page where the shopper never touches the cart the
        //  strip does not exist at the moment this runs. Repaint when the
        //  mini-cart is shown.
        document.addEventListener('click', function (e) {
            if (e.target.closest && e.target.closest('.action.showcart')) {
                //  After Knockout has had a turn.
                setTimeout(update, 0);
            }
        }, true);
    };
});
