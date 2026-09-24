/**
 * Hub Market override of Algolia_AlgoliaSearch/js/recommend.js (DEV07).
 *
 * Same placements and config switches as upstream (PDP / cart / trending widget
 * / looking-similar widget), with four changes:
 *   1. classNames give the recommend-js markup the theme's rail structure
 *      (`.block.widget > .block-content > ol.product-items > li.product-item`),
 *      so the existing rail + card CSS styles it — LTR and RTL.
 *   2. queryParameters.clickAnalytics: every carousel gets a queryID, which the
 *      cards put on links and Add to Cart (template/recommend/products.js), and
 *      the shopper's token when they have consented — so recommendation clicks
 *      and conversions reach Algolia Analytics.
 *   3. A block with no recommendations renders NOTHING (upstream's widget
 *      variants still printed their title over an empty box).
 *   4. "Recommended Product Clicked" is sent (queryID + position) through
 *      window.algoliaInsights, which insights-hm-mixin.js gates on consent.
 * Loaded through RequireJS after the page — no effect on first paint.
 */
define([
    'jquery',
    'algoliaRecommendLib',
    'algoliaRecommendJsLib',
    'recommendProductsHtml',
    'algoliaCommon',
    'domReady!'
], function ($, recommend, recommendJs, recommendProductsHtml, algoliaCommon) {
    'use strict';

    if (typeof algoliaConfig === 'undefined') {
        return;
    }

    var CLASS_NAMES = {
        root: 'block widget block-products-list grid hm-rec',
        container: 'block-content products-grid grid',
        list: 'product-items widget-product-grid',
        item: 'product-item'
    };

    function hasConsent() {
        var c = algoliaConfig.cookieConfiguration || {};

        return !c.cookieRestrictionModeEnabled || !!algoliaCommon.getCookie(c.consentCookieName);
    }

    function queryParameters() {
        var params = {clickAnalytics: true};
        var token = algoliaCommon.getCookie('_ALGOLIA');

        if (token && hasConsent()) {
            params.userToken = token;
        }

        return params;
    }

    var clicksBound = false;

    function bindClicks() {
        if (clicksBound) {
            return;
        }

        clicksBound = true;
        document.addEventListener('click', function (e) {
            var a = e.target.closest && e.target.closest('.hm-rec a.hm-rec__link');

            if (!a || !window.algoliaInsights || typeof window.algoliaInsights.trackClick !== 'function') {
                return;
            }

            var queryID = a.getAttribute('data-queryid');

            if (!queryID) {
                return;
            }

            window.algoliaInsights.trackClick({
                eventName: 'Recommended Product Clicked',
                index: a.getAttribute('data-index'),
                objectIDs: [a.getAttribute('data-objectid')],
                queryID: queryID,
                positions: [parseInt(a.getAttribute('data-position'), 10) || 1]
            });
        }, true);
    }

    /*
     * A model that is not trained yet (Frequently Bought Together and Trending
     * need conversion volume) answers 404 "No recommendations found", which
     * recommend-js leaves as an unhandled rejection — a console error on every
     * product page. Treat any failed call as "no recommendations": the block
     * then renders nothing, which is exactly the required empty behaviour.
     */
    function quietClient(client) {
        var wrapped = Object.create(client);

        Object.keys(client).concat(Object.keys(Object.getPrototypeOf(client) || {})).forEach(function (key) {
            if (typeof client[key] === 'function' && /^get/.test(key)) {
                wrapped[key] = function (queries) {
                    return Promise.resolve(client[key].apply(client, arguments)).catch(function () {
                        return {results: (Array.isArray(queries) ? queries : [queries]).map(function () {
                            return {hits: []};
                        })};
                    });
                };
            }
        });

        return wrapped;
    }

    return function (config) {
        var rc = algoliaConfig.recommend;
        var recommendClient = quietClient(recommend(algoliaConfig.applicationId, algoliaConfig.apiKey));
        var indexName = algoliaConfig.indexName + '_products';
        var objectIDs = config.objectIDs;
        var body = document.body.classList;
        var onPdp = body.contains('catalog-product-view');
        var onCart = body.contains('checkout-cart-index');

        function mount(widget, container, title, addToCart, max, extra) {
            if (!document.querySelector(container)) {
                return;
            }

            widget($.extend({
                container: container,
                recommendClient: recommendClient,
                indexName: indexName,
                maxRecommendations: max,
                classNames: CLASS_NAMES,
                queryParameters: queryParameters(),
                transformItems: function (items) {
                    return items.map(function (item, i) {
                        return $.extend({}, item, {position: i + 1});
                    });
                },
                headerComponent: function (props) {
                    return props.recommendations && props.recommendations.length
                        ? recommendProductsHtml.getHeaderHtml(props.html, title)
                        : '';
                },
                itemComponent: function (props) {
                    return recommendProductsHtml.getItemHtml(props.item, props.html, addToCart);
                },
                emptyComponent: function () {
                    return null;
                }
            }, extra || {}));
        }

        bindClicks();

        if ((rc.enabledFBT && onPdp) || (rc.enabledFBTInCart && onCart)) {
            mount(recommendJs.frequentlyBoughtTogether, '#frequentlyBoughtTogether', rc.FBTTitle,
                rc.isAddToCartEnabledInFBT, rc.limitFBTProducts, {objectIDs: objectIDs});
        }

        if ((rc.enabledRelated && onPdp) || (rc.enabledRelatedInCart && onCart)) {
            mount(recommendJs.relatedProducts, '#relatedProducts', rc.relatedProductsTitle,
                rc.isAddToCartEnabledInRelatedProduct, rc.limitRelatedProducts, {objectIDs: objectIDs});
        }

        if ((rc.isTrendItemsEnabledInPDP && onPdp) || (rc.isTrendItemsEnabledInCartPage && onCart)) {
            mount(recommendJs.trendingItems, '#trendItems', rc.trendingItemsTitle,
                rc.isAddToCartEnabledInTrendsItem, rc.limitTrendingItems,
                {facetName: rc.trendItemFacetName || '', facetValue: rc.trendItemFacetValue || ''});
        } else if (rc.enabledTrendItems && config.recommendTrendContainer) {
            mount(recommendJs.trendingItems, '#' + config.recommendTrendContainer, rc.trendingItemsTitle,
                rc.isAddToCartEnabledInTrendsItem,
                config.numOfTrendsItem ? parseInt(config.numOfTrendsItem, 10) : rc.limitTrendingItems,
                {facetName: config.facetName || '', facetValue: config.facetValue || ''});
        }

        if ((rc.isLookingSimilarEnabledInPDP && onPdp) || (rc.isLookingSimilarEnabledInCartPage && onCart)) {
            mount(recommendJs.lookingSimilar, '#lookingSimilar', rc.lookingSimilarTitle,
                rc.isAddToCartEnabledInLookingSimilar, rc.limitLookingSimilar, {objectIDs: objectIDs});
        } else if (rc.enabledLookingSimilar && objectIDs && config.recommendLSContainer) {
            mount(recommendJs.lookingSimilar, '#' + config.recommendLSContainer, rc.lookingSimilarTitle,
                rc.isAddToCartEnabledInLookingSimilar,
                config.numOfLookingSimilarItem ? parseInt(config.numOfLookingSimilarItem, 10) : rc.limitLookingSimilar,
                {objectIDs: objectIDs});
        }
    };
});
