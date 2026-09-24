/**
 * Hub Market: on checkout the header cart icon is a plain link to the cart page.
 *
 * Elsewhere, clicking it opens the mini-cart dropdown (dropdownDialog, bound on the link
 * itself). On checkout that dropdown only offers "Proceed to Checkout", which is where the
 * shopper already is, so the click is stopped before it reaches the dropdown's handler and
 * the link's own href (/<store>/checkout/cart/) is followed as normal. Loaded on the checkout
 * handle only; see css/hm-checkout-header.css for the icon being shown at all.
 */
(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var link = event.target && event.target.closest
            && event.target.closest('.page-header .minicart-wrapper .action.showcart');

        if (link && link.getAttribute('href')) {
            /* Capture phase: the dropdown never sees it; no preventDefault, so the link navigates. */
            event.stopPropagation();
        }
    }, true);
}());
