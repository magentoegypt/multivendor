/**
 * Hub Market — cart quantity stepper.
 *
 * The Figma cart replaces the bare number field with a −/+ stepper, which is a
 * behavioural change as much as a visual one: a stepper implies the number takes
 * effect when you press it, where Magento's cart requires a separate "Update
 * Shopping Cart" submit.
 *
 * HOW IT COMMITS, AND WHY THIS WAY
 * --------------------------------
 * It clicks Magento's own Update button rather than posting anything itself.
 * That matters: the button carries `name="update_cart_action" value="update_qty"`,
 * and a programmatic `form.submit()` would NOT include a submit button's
 * name/value pair, so the controller would receive no action and quietly do
 * nothing. Going through the real button also keeps
 * Magento_Checkout/js/action/update-shopping-cart's validation in the path.
 *
 * The commit is debounced. Pressing + three times should be one round trip, not
 * three, and each press restarts the clock. Magento's cart update is a full page
 * POST — there is no ajax qty endpoint in core — so the reload is the cost of
 * the affordance, and the debounce is what keeps it to one.
 *
 * Delegated from the cart container, once, rather than instantiated per line:
 * the rows are re-rendered on every update, and a per-row widget would need
 * re-binding each time.
 */
define(['jquery'], function ($) {
    'use strict';

    var COMMIT_DELAY = 650;

    return function (config, element) {
        var $root = $(element),
            timer = null;

        /**
         * Magento's own submit path — see the note above on why not form.submit().
         */
        function commit() {
            var $update = $('button.action.update[name="update_cart_action"]').first();

            if ($update.length) {
                $update.trigger('click');
            }
        }

        function schedule() {
            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(commit, COMMIT_DELAY);
        }

        $root.on('click', '[data-hm-qty]', function (event) {
            var $btn = $(this),
                $field = $btn.closest('.hm-qty').find('input[data-role="cart-item-qty"]').first(),
                step = $btn.data('hmQty') === 'up' ? 1 : -1,
                current,
                next;

            event.preventDefault();

            if (!$field.length) {
                return;
            }

            current = parseFloat($field.val());

            if (isNaN(current)) {
                current = 1;
            }

            /*
             * Floored at 1, not 0. Magento treats a posted 0 as "remove", and a
             * shopper pressing minus is adjusting an amount, not deleting a line
             * — the trash control immediately above does that, deliberately and
             * with a confirmation. Silently emptying the row out from under a
             * mis-click is the kind of thing you cannot undo from the cart.
             */
            next = Math.max(1, current + step);

            if (next === current) {
                return;
            }

            $field.val(next).trigger('change');
            schedule();
        });

        /*
         * A value typed straight into the field commits on the same debounce, so
         * the field and the buttons behave identically. `change` only fires on
         * blur/Enter, which is the right moment — not on every keystroke.
         */
        $root.on('change', 'input[data-role="cart-item-qty"]', function () {
            var value = parseFloat($(this).val());

            if (!isNaN(value) && value > 0) {
                schedule();
            }
        });
    };
});
