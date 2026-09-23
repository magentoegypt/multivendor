/**
 * Algolia front-end hooks — Hub Market.
 *
 * Upstream ships this file empty; it is the documented override point and is
 * already a requirejs dependency of autocomplete.js, so it runs before the
 * autocomplete options are built.
 * https://www.algolia.com/doc/integration/magento-2/customize/custom-front-end-events/
 *
 * WHY: autocomplete-js sets no `panelContainer`, so it portals the suggestions
 * panel to <body> and sizes it from the input wrapper. Measured, that put the
 * panel at x=414 w=802 under a field at x=313 w=903 — visibly offset, and no
 * amount of `inline-size: 100%` in CSS could fix it because the percentage
 * resolved against <body>, not the search bar.
 *
 * Re-parenting the panel into the field wrapper (which _hm-algolia.less gives
 * `position: relative`) makes the CSS percentage resolve against the bar, so the
 * panel tracks the field at any viewport width instead of being pinned with
 * hard-coded pixels.
 */
define(['algoliaCommon'], function (algoliaCommon) {
    'use strict';

    //  The Insights / Personalization rules (consent, Free-plan query guards)
    //  live in insights-hm-mixin.js, which is applied before insights.js runs.

    /*
     * Hand over from the server-rendered field (autocomplete.phtml,
     * #hm-search-prefield) to Algolia's the moment Algolia's is mounted. Whatever
     * the visitor typed while the scripts were loading, and their focus, move
     * across, so the swap is invisible even mid-typing.
     */
    algoliaCommon.registerHook('afterAutocompleteStart', function (instance) {
        var pre = document.getElementById('hm-search-prefield');

        if (!pre) {
            return instance;
        }

        var input = pre.querySelector('input');
        var typed = input ? input.value : '';
        var hadFocus = !!input && document.activeElement === input;

        pre.parentNode.removeChild(pre);

        var aaInput = document.querySelector('#algoliaAutocomplete .aa-Input');

        if (typed && instance && typeof instance.setQuery === 'function') {
            instance.setQuery(typed);

            if (hadFocus) {
                instance.setIsOpen(true);
                instance.refresh();
            }
        }

        if (hadFocus && aaInput) {
            aaInput.focus();
        }

        return instance;
    });

    algoliaCommon.registerHook('beforeAutocompleteOptions', function (options) {
        var mount = document.querySelector('.hm-search__field');

        if (mount) {
            options.panelContainer = mount;
        }

        return options;
    });
});
