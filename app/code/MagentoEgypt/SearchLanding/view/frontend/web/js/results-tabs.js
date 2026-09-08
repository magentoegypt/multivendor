/**
 * Hub Market — search result tabs on the full results page.
 *
 * The Products panel is not a panel: it is the page. Magento's grid, its
 * toolbar, its layered navigation and its pager all live in `.columns`, so
 * selecting Products means showing that and hiding the two short lists, and
 * selecting Vendors or Categories means the reverse.
 *
 * No fetching and no URL change — every tab's content is already in the HTML.
 * Switching is presentation only, so the back button and a reload both land the
 * shopper back on Products with their filters and page number intact.
 */
define([], function () {
    'use strict';

    return function (config, element) {
        var tabs = element.querySelectorAll('.hm-live__tab'),
            panels = element.querySelectorAll('.hm-srp__panel'),
            // The grid and sidebar are OUTSIDE this block — they belong to the
            // page, not to the header — so they are looked up from the document.
            columns = document.querySelector('.columns');

        if (!tabs.length) {
            return;
        }

        /**
         * Fill in the product count from the toolbar.
         *
         * The toolbar is the only element on the page that has genuinely counted
         * the results. Every server-side alternative either disagrees with the
         * grid or — in the case of asking the search layer from this block, which
         * renders first — re-runs the search early enough to change it.
         *
         * Reads `data-total` rather than the visible text: the text is localised
         * ("٢١ نتيجة" on the Arabic store), and parsing Arabic-Indic numerals back
         * out of it would be a needless second place to get numbers wrong.
         */
        (function () {
            var amount = document.querySelector('.toolbar-amount[data-total]'),
                summary = element.querySelector('.hm-live__summary'),
                productsTab = element.querySelector('.hm-live__tab[data-panel="products"]'),
                total,
                facets;

            if (!amount) {
                return;
            }
            total = parseInt(amount.getAttribute('data-total'), 10);

            if (isNaN(total)) {
                return;
            }

            if (productsTab && productsTab.getAttribute('data-label')) {
                productsTab.textContent =
                    productsTab.getAttribute('data-label').replace('%1', String(total));
            }

            if (summary && summary.getAttribute('data-label')) {
                facets = parseInt(summary.getAttribute('data-facets'), 10) || 0;
                summary.textContent =
                    summary.getAttribute('data-label').replace('%1', String(total + facets));
            }
        }());

        function select(wanted) {
            Array.prototype.forEach.call(tabs, function (tab) {
                var on = tab.getAttribute('data-panel') === wanted;

                tab.classList.toggle('is-active', on);
                tab.setAttribute('aria-selected', on ? 'true' : 'false');
            });

            Array.prototype.forEach.call(panels, function (panel) {
                panel.hidden = panel.getAttribute('data-panel') !== wanted;
            });

            if (columns) {
                columns.hidden = wanted !== 'products';
            }
        }

        element.addEventListener('click', function (e) {
            var tab = e.target.closest ? e.target.closest('.hm-live__tab') : null;

            if (tab) {
                select(tab.getAttribute('data-panel'));
            }
        });
    };
});
