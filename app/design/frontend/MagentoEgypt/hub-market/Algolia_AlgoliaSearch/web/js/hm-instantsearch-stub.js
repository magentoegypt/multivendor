/**
 * Hub Market — stand-in for Algolia's InstantSearch library, for common.js ONLY.
 *
 * Algolia_AlgoliaSearch/js/internals/common requires `algoliaInstantSearchLib`
 * (84 KB compressed, ~300 KB of script to parse) on EVERY page, because at load
 * it builds a URL router with `instantsearch.routers.history(...)`. That router is
 * only ever used by InstantSearch widgets, and InstantSearch is switched off on
 * this site (algoliasearch_instant/instant/is_instant_enabled = 0): the search
 * results page is rendered by Magento through the SearchAdapter, and the header
 * uses autocomplete, which does not need the library.
 *
 * requirejs-config.js maps the library to this file for common.js alone, so
 * anything that requires InstantSearch directly still gets the real one.
 *
 * !!! IF INSTANTSEARCH IS EVER ENABLED, DELETE THE MAP ENTRY in the theme's
 * requirejs-config.js. With this stub in place common.js's routing object is
 * inert, and InstantSearch would lose URL state (back button, shared links).
 */
define([], function () {
    'use strict';

    return {
        routers: {
            history: function () {
                return {};
            }
        }
    };
});
