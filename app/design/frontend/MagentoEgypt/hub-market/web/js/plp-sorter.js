/**
 * Hub Market — PLP sort control.
 *
 * Core's productListToolbarForm widget sets ONE parameter per control: the
 * select drives `product_list_order`, the toggle button drives
 * `product_list_dir`. Figma has no toggle, so each option here has to carry both
 * — the value is "order:dir" and this sets the pair together.
 *
 * The rest of the query string is preserved rather than rebuilt. A shopper
 * sorting a filtered listing must keep their filters, their page size and their
 * view mode; dropping to a bare ?product_list_order=… would silently clear the
 * layered navigation they just set up.
 */
define([], function () {
    'use strict';

    return function (config, element) {
        element.addEventListener('change', function () {
            var parts = String(element.value).split(':'),
                order = parts[0],
                dir = parts[1] === 'desc' ? 'desc' : 'asc',
                url = new URL(window.location.href);

            if (!order) {
                return;
            }

            url.searchParams.set('product_list_order', order);
            url.searchParams.set('product_list_dir', dir);
            // Any sort change invalidates the current page offset — staying on
            // page 4 of a freshly reordered listing lands the shopper nowhere
            // meaningful.
            url.searchParams.delete('p');

            window.location.assign(url.toString());
        });
    };
});
