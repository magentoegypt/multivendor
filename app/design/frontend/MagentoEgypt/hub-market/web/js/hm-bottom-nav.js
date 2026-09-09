/**
 * Hub Market — the cart count on the mobile bottom bar (CL036-DEV01.13).
 *
 * The bar itself, including which tab is current, is rendered by the server;
 * this only paints the number on the Cart tab. It is deliberately the ONLY
 * scripted part of that component, and its absence changes nothing but the
 * badge — see the note in bottom-nav.phtml.
 *
 * `customer-data` is the section store Magento already keeps for the mini-cart,
 * so this reads the same value the header shows and updates on the same events.
 * No second request is made.
 */
define(['Magento_Customer/js/customer-data'], function (customerData) {
    'use strict';

    return function (config, element) {
        var badge = element.querySelector('.hm-bottomnav__count'),
            cart;

        if (!badge) {
            return;
        }

        cart = customerData.get('cart');

        /**
         * @returns {void}
         */
        function paint() {
            var count = Number((cart() || {}).summary_count) || 0;

            //  99+ rather than a four-digit number: the badge is a 16px circle
            //  and the tab under it is 20% of a phone.
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.hidden = count === 0;
        }

        cart.subscribe(paint);
        paint();
    };
});
