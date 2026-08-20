/**
 * Hub Market — minicart quantity stepper.
 *
 * The sibling of js/hm-cart-qty.js, and it commits the same way: by clicking a
 * button Magento already owns, not by posting anything itself.
 *
 * On the cart page that button is "Update Shopping Cart". Here it is the
 * per-item `.update-cart-item` button that core renders hidden beside each qty
 * field. Magento_Checkout/js/sidebar.js binds a click handler to it that reads
 * the qty straight out of `#cart-item-<id>-qty` and ajaxes it to
 * checkout/sidebar/updateItemQty. Going through that button keeps the whole of
 * core's path — validation, the section reload, and the `ajax:updateCartItemQty`
 * event other blocks listen for.
 *
 * Delegated from the minicart wrapper because Knockout re-renders the item list
 * on every update, so anything bound per row would be bound to a dead node the
 * moment it succeeded.
 */
define(['jquery'], function ($) {
    'use strict';

    var COMMIT_DELAY = 650;

    return function (config, element) {
        var $root = $(element),
            timers = {};

        /**
         * One timer per line: two lines being adjusted in the same breath must
         * not cancel each other's commit the way a single shared timer would.
         */
        function schedule(itemId) {
            if (timers[itemId]) {
                window.clearTimeout(timers[itemId]);
            }

            timers[itemId] = window.setTimeout(function () {
                delete timers[itemId];
                $('#update-cart-item-' + itemId).trigger('click');
            }, COMMIT_DELAY);
        }

        $root.on('click', '[data-role="hm-minicart-qty"] [data-hm-qty]', function (event) {
            var $btn = $(this),
                $field = $btn.closest('[data-role="hm-minicart-qty"]').find('input.cart-item-qty').first(),
                step = $btn.data('hmQty') === 'up' ? 1 : -1,
                itemId,
                current,
                next;

            event.preventDefault();

            if (!$field.length) {
                return;
            }

            itemId = $field.data('cartItem');
            current = parseFloat($field.val());

            if (isNaN(current)) {
                current = 1;
            }

            /*
             * Floored at 1, as on the cart page. Core's own _isValidQty rejects
             * anything below 1 anyway, so a 0 would leave the field showing a
             * number the cart does not hold — worse than not moving at all. The
             * remove control above the stepper is how a line gets deleted.
             */
            next = Math.max(1, current + step);

            if (next === current) {
                return;
            }

            /*
             * `change` is what makes core reveal its update button; it has to
             * fire before the click is scheduled or there is nothing to click.
             */
            $field.val(next).trigger('change');
            schedule(itemId);
        });
    };
});
