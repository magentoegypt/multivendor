/**
 * Hub Market — wishlist count badge (CL036-TC96).
 *
 * QA: "when add product to wishlist no counter show on icon". The header heart
 * was a plain link; the count existed only on the account page.
 *
 * The number comes from Magento's own `wishlist` customer-data section, the same
 * private-content channel the mini-cart counter uses, so it is right on a
 * full-page-cached header and refreshes by itself: Magento_Wishlist's
 * sections.xml invalidates the section on add, remove, update and move-to-cart.
 * The section carries the count only inside its label ("3 items", "1 item", null
 * when empty), so the first number in it is read; the Arabic store prints Latin
 * digits, so the same read works there.
 *
 * Every element marked [data-hm-wishlist-count] is a badge: the header icon and
 * the phone menu's Wishlist link. Hidden at zero.
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data'
], function ($, customerData) {
    'use strict';

    /**
     * @param {Object} data the wishlist section
     * @returns {Number}
     */
    function countOf(data) {
        var match = String((data && data.counter) || '').match(/\d+/);

        return match ? parseInt(match[0], 10) : 0;
    }

    return function () {
        var wishlist = customerData.get('wishlist');

        function render(data) {
            var count = countOf(data);

            $('[data-hm-wishlist-count]').each(function () {
                //  The badges are styled inline, and an inline display beats the
                //  hidden attribute, so the display value itself is switched: the
                //  attribute names the one to use when shown.
                var shown = count > 0;

                $(this)
                    .text(count > 99 ? '99+' : String(count))
                    .prop('hidden', !shown)
                    .css('display', shown ? ($(this).attr('data-hm-wishlist-count') || 'inline-flex') : 'none');
            });
        }

        render(wishlist());
        wishlist.subscribe(render);
    };
});
