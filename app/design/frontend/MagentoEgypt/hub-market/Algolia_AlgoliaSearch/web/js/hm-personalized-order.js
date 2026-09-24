/**
 * Hub Market — personalized product order on cached listing pages (DEV05).
 *
 * Category pages and the search results page are rendered by Magento (the
 * SearchAdapter asks Algolia server-side, with no shopper token) and cached in
 * Varnish for everyone, so they cannot carry one shopper's personalization.
 * This re-orders the products ALREADY ON THE PAGE for the current shopper,
 * after load, without touching the cache:
 *
 *   1. Ask Algolia for exactly these products twice in ONE request — with and
 *      without personalization, same query, same shopper token.
 *   2. The difference between the two orders is what personalization did.
 *      Apply that movement to the page's own (merchandised) order, so the
 *      store's category positions still count and only the personal lift is
 *      added on top.
 *   3. If personalization moved nothing, touch nothing.
 *
 * Runs only with cookie consent, a tracking token (_ALGOLIA cookie) and the
 * default sort. Never adds or removes products; pagination is unaffected.
 */
define(['algoliaCommon'], function (algoliaCommon) {
    'use strict';

    // The page's own listing (Magento list.phtml), not a product widget.
    var LIST = '.products.wrapper > ol.product-items';
    var ITEM = 'li.product-item';
    var ID = '[data-role="priceBox"][data-product-id]';

    function hasConsent(cfg) {
        var cookieCfg = (cfg && cfg.cookieConfiguration) || {};

        return !cookieCfg.cookieRestrictionModeEnabled || !!algoliaCommon.getCookie(cookieCfg.consentCookieName);
    }

    function defaultSort() {
        var params = new URLSearchParams(window.location.search);

        return !params.get('product_list_order') && !params.get('product_list_dir');
    }

    function rankMap(hits) {
        var map = {};

        hits.forEach(function (hit, i) {
            map[String(hit.objectID)] = i;
        });

        return map;
    }

    function run(cfg) {
        var list = document.querySelector(LIST);
        var token = algoliaCommon.getCookie('_ALGOLIA');

        if (!list || !token || !hasConsent(cfg) || !defaultSort() || !cfg.applicationId || !cfg.apiKey) {
            return;
        }

        var items = Array.prototype.slice.call(list.querySelectorAll(':scope > ' + ITEM));
        var ids = items.map(function (li) {
            var box = li.querySelector(ID);

            return box ? String(box.getAttribute('data-product-id')) : null;
        });

        if (items.length < 2 || ids.indexOf(null) !== -1) {
            return;
        }

        var query = document.body.classList.contains('catalogsearch-result-index')
            ? (new URLSearchParams(window.location.search).get('q') || '')
            : '';
        var base = {
            query: query,
            filters: ids.map(function (id) { return 'objectID:' + id; }).join(' OR '),
            hitsPerPage: ids.length,
            attributesToRetrieve: ['objectID'],
            attributesToHighlight: [],
            attributesToSnippet: [],
            analytics: false,
            clickAnalytics: false,
            userToken: token
        };
        var indexName = cfg.indexName + '_products';

        function request(personalized) {
            var params = Object.assign({}, base, {enablePersonalization: personalized});

            return {
                indexName: indexName,
                params: Object.keys(params).map(function (k) {
                    var v = params[k];

                    return k + '=' + encodeURIComponent(typeof v === 'string' ? v : JSON.stringify(v));
                }).join('&')
            };
        }

        fetch('https://' + cfg.applicationId + '-dsn.algolia.net/1/indexes/*/queries', {
            method: 'POST',
            headers: {
                'X-Algolia-Application-Id': cfg.applicationId,
                'X-Algolia-API-Key': cfg.apiKey,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({requests: [request(true), request(false)]})
        }).then(function (res) {
            return res.ok ? res.json() : null;
        }).then(function (data) {
            if (!data || !data.results || data.results.length !== 2) {
                return;
            }

            var withP = rankMap(data.results[0].hits || []);
            var without = rankMap(data.results[1].hits || []);
            var moved = false;
            var keyed = items.map(function (li, i) {
                var id = ids[i];
                var lift = (id in withP && id in without) ? without[id] - withP[id] : 0;

                if (lift !== 0) {
                    moved = true;
                }

                // Page position minus the personal lift; ties keep page order.
                return {li: li, key: i - lift, i: i};
            });

            if (!moved) {
                return;
            }

            keyed.sort(function (a, b) {
                return a.key - b.key || a.i - b.i;
            });

            var frag = document.createDocumentFragment();

            keyed.forEach(function (k) {
                frag.appendChild(k.li);
            });
            list.appendChild(frag);
            list.setAttribute('data-hm-personalized', '1');
        }).catch(function () {
            // Personalization is an enhancement: the page is already complete.
        });
    }

    return function () {
        var cfg = window.algoliaConfig;

        var body = document.body.classList;

        if (!cfg || !window.fetch || !window.URLSearchParams ||
            !(body.contains('catalog-category-view') || body.contains('catalogsearch-result-index'))) {
            return;
        }

        run(cfg);
    };
});
