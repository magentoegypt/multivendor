/**
 * Hub Market — Algolia search analytics + click/conversion events on the
 * server-rendered search results page (DEV06 "track click, add-to-cart and
 * purchase from search results").
 *
 * The page is rendered by Magento (SearchAdapter) and cached, so it carries no
 * queryID. After load this records the search from the browser with
 * clickAnalytics on (the server-side query is excluded from Analytics, see
 * AlgoliaVendor\Plugin\ServerSearchNoAnalytics) and uses the returned queryID:
 *   - a "Clicked" event (queryID + position) when a result is opened;
 *   - data-queryid on each result's Add to Cart button, and ?queryID= on its
 *     links, which the extension's add-to-cart mixin turns into add-to-cart and
 *     purchase conversions attributed to this search.
 * Events go through window.algoliaInsights, whose senders are consent-gated by
 * insights-hm-mixin.js; the search itself carries a userToken only with consent.
 */
define(['algoliaCommon'], function (algoliaCommon) {
    'use strict';

    var LIST = '.products.wrapper > ol.product-items';
    var ITEM = 'li.product-item';
    var ID = '[data-role="priceBox"][data-product-id]';

    function hasConsent(cfg) {
        var c = (cfg && cfg.cookieConfiguration) || {};

        return !c.cookieRestrictionModeEnabled || !!algoliaCommon.getCookie(c.consentCookieName);
    }

    function pageInfo() {
        var params = new URLSearchParams(window.location.search);
        var limiter = document.querySelector('.limiter-options');
        var limit = parseInt((limiter && limiter.value) || params.get('product_list_limit') || '', 10);

        return {
            q: params.get('q') || '',
            page: Math.max(parseInt(params.get('p') || '1', 10) || 1, 1),
            limit: limit > 0 ? limit : 12
        };
    }

    function productId(li) {
        var box = li.querySelector(ID);

        return box ? String(box.getAttribute('data-product-id')) : null;
    }

    function withQueryId(href, queryID) {
        try {
            var url = new URL(href, window.location.href);

            url.searchParams.set('queryID', queryID);

            return url.toString();
        } catch (e) {
            return href;
        }
    }

    function decorate(list, queryID) {
        Array.prototype.forEach.call(list.querySelectorAll(':scope > ' + ITEM), function (li) {
            Array.prototype.forEach.call(li.querySelectorAll('a.product-item-link, a.product-item-photo, a.product.photo'), function (a) {
                a.href = withQueryId(a.getAttribute('href'), queryID);
            });
            Array.prototype.forEach.call(li.querySelectorAll('button.tocart, button[type="submit"]'), function (b) {
                b.setAttribute('data-queryid', queryID);
            });
        });
    }

    function bindClicks(list, cfg, info, queryID) {
        list.addEventListener('click', function (e) {
            var a = e.target.closest && e.target.closest('a');
            var li = e.target.closest && e.target.closest(ITEM);

            if (!a || !li || !window.algoliaInsights || typeof window.algoliaInsights.trackClick !== 'function') {
                return;
            }

            var items = Array.prototype.slice.call(list.querySelectorAll(':scope > ' + ITEM));
            var id = productId(li);

            if (!id || li.getAttribute('data-hm-clicked')) {
                return;
            }

            li.setAttribute('data-hm-clicked', '1');
            // Position as shown NOW (after any personalized re-order), 1-based across pages.
            window.algoliaInsights.trackClick({
                eventName: 'Clicked',
                index: cfg.indexName + '_products',
                objectIDs: [id],
                queryID: queryID,
                positions: [(info.page - 1) * info.limit + items.indexOf(li) + 1]
            });
        }, true);
    }

    return function () {
        var cfg = window.algoliaConfig;

        if (!cfg || !cfg.applicationId || !cfg.apiKey || !window.fetch || !window.URLSearchParams ||
            !document.body.classList.contains('catalogsearch-result-index')) {
            return;
        }

        var info = pageInfo();
        var params = {
            query: info.q,
            page: info.page - 1,
            hitsPerPage: info.limit,
            attributesToRetrieve: ['objectID'],
            attributesToHighlight: [],
            attributesToSnippet: [],
            analytics: true,
            clickAnalytics: true,
            enablePersonalization: false,
            analyticsTags: ['results-page']
        };
        var token = algoliaCommon.getCookie('_ALGOLIA');

        if (token && hasConsent(cfg)) {
            params.userToken = token;
        }

        fetch('https://' + cfg.applicationId + '-dsn.algolia.net/1/indexes/' +
            encodeURIComponent(cfg.indexName + '_products') + '/query', {
            method: 'POST',
            headers: {
                'X-Algolia-Application-Id': cfg.applicationId,
                'X-Algolia-API-Key': cfg.apiKey,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(params)
        }).then(function (res) {
            return res.ok ? res.json() : null;
        }).then(function (data) {
            var list = document.querySelector(LIST);

            if (!data || !data.queryID || !list) {
                return; // A no-results search is still recorded in Analytics.
            }

            list.setAttribute('data-hm-queryid', data.queryID);
            decorate(list, data.queryID);
            bindClicks(list, cfg, info, data.queryID);
        }).catch(function () {
            // Tracking is an enhancement; the page is already complete.
        });
    };
});
