/**
 * Hub Market — mobile filter drawer.
 *
 * CL036-DEV01.02 item 2: on a phone Magento drops the whole layered-navigation
 * rail above the product grid, so the visitor meets five open accordions and
 * scrolls ~530px before reaching a product. The reference puts filters behind a
 * single "Filters" pill next to the result count.
 *
 * This adds the pill to the toolbar and moves nothing in the DOM: the rail is
 * the same `.sidebar-main` element, taken off-canvas by CSS and slid in when
 * `hm-filters-open` is set on <html>. Keeping the markup in place means the
 * filter links, their form and their JS all still work, and with this script
 * absent (or JS off) the rail simply renders where it always did — the CSS that
 * hides it is scoped to `.hm-filters-ready`, which only this file sets.
 *
 * Desktop is untouched: everything below no-ops above the breakpoint, and the
 * class is removed again if the window is widened.
 */
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    var BREAKPOINT = 767,
        OPEN = 'hm-filters-open',
        READY = 'hm-filters-ready';

    return function (config, element) {
        var $sidebar = $(element),
            $root = $('html'),
            $toolbar = $('.toolbar-products').first(),
            $toggle,
            $scrim;

        if (!$sidebar.length || !$sidebar.find('.filter-options-item').length) {
            return;
        }

        /**
         * @returns {Boolean}
         */
        function isMobile() {
            return window.matchMedia('(max-width: ' + BREAKPOINT + 'px)').matches;
        }

        /**
         * @param {Boolean} open
         */
        function setOpen(open) {
            $root.toggleClass(OPEN, open);
            $toggle.attr('aria-expanded', open ? 'true' : 'false');

            if (open) {
                // Appended beside the sidebar, NOT to <body>: `.page-products
                // .columns` is `position: relative; z-index: 1`, a stacking
                // context of its own, so the drawer's z-index 1000 only ranks
                // inside it. A body-level scrim (999, root context) painted
                // OVER the drawer and swallowed every tap, and the sticky
                // header (root, 90) covered its top 500px. Sharing the
                // context fixes the first; _hm-mobile-parity.less raises
                // .columns above the header while open for the second.
                $scrim = $('<div class="hm-filters-scrim"></div>').appendTo($sidebar.parent()).on('click', function () {
                    setOpen(false);
                });
                //  Move focus into the drawer so a keyboard or screen-reader
                //  user lands where the button says they will.
                $sidebar.attr('tabindex', '-1').trigger('focus');
            } else if ($scrim) {
                $scrim.remove();
                $scrim = null;
                $toggle.trigger('focus');
            }
        }

        $toggle = $('<button class="hm-filters-toggle" type="button" aria-expanded="false"></button>')
            .attr('aria-controls', $sidebar.attr('id') || 'hm-plp-filters')
            .text($t('Filters'))
            .on('click', function () {
                setOpen(!$root.hasClass(OPEN));
            });

        if (!$sidebar.attr('id')) {
            $sidebar.attr('id', 'hm-plp-filters');
        }

        //  Prepend so the pill is the toolbar's first child — the reference
        //  order is [ Filters | N results | sort ].
        $toolbar.prepend($toggle);

        $sidebar.prepend(
            $('<button class="hm-filters-close" type="button"></button>')
                .attr('aria-label', $t('Close filters'))
                .html('&times;')
                .on('click', function () {
                    setOpen(false);
                })
        );

        $root.addClass(READY);

        $(document).on('keydown', function (e) {
            if (e.key === 'Escape' && $root.hasClass(OPEN)) {
                setOpen(false);
            }
        });

        //  A rotation to landscape or a resize past the breakpoint must not
        //  leave the page scroll-locked behind a drawer that is no longer shown.
        $(window).on('resize orientationchange', function () {
            if (!isMobile() && $root.hasClass(OPEN)) {
                setOpen(false);
            }
        });
    };
});
