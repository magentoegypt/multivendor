/**
 * Hub Market — category sheet on phones.
 *
 * Bound to <details class="hm-nav__menu">. Its <summary> is the phone header's
 * hamburger (lifted into the header row by _hm-mobile-parity.less), so opening
 * and closing are native and need no script. This only keeps the page from
 * scrolling behind the open sheet, and drops the lock again when the sheet
 * closes — including the Escape handled by HubMarket_DynamicMenu.
 */
define(['jquery'], function ($) {
    'use strict';

    var OPEN = 'hm-menu-open';

    return function (config, element) {
        var $root = $(document.documentElement);

        function sync() {
            $root.toggleClass(OPEN, !!element.open);
        }

        element.addEventListener('toggle', sync);
        sync();
    };
});
