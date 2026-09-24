/**
 * Hub Market rules for Algolia Insights / Personalization (DEV05).
 *
 * A RequireJS mixin, not a patch from hooks.js: insights.js initialises itself
 * on DOM ready and fires the product-view event from there, and hooks.js is only
 * pulled in later by autocomplete.js — so a patch there raced the very event it
 * had to stop. A mixin is applied as the module is defined, before any of its
 * methods can run.
 */
define(['algoliaCommon', 'algoliaAnalyticsLib'], function (algoliaCommon, algoliaAnalyticsWrapper) {
    'use strict';

    /*
     * The extension's "Enable Personalization" switch also adds
     * `enablePersonalization: true` to every search. On this app's plan Algolia
     * rejects such a search outright — HTTP 402 "EnablePersonalization is not
     * supported on this plan" (measured 2026-09-23) — which would blank the
     * autocomplete for everyone. The personalization EVENTS are accepted on every
     * plan and are the history Personalization learns from, so the switch stays
     * on and only the query flag is removed.
     *
     * 2026-09-24: the app is on Grow Plus, which includes Personalization; a
     * search with enablePersonalization now returns 200 with _rankingInfo.
     * personalization, so the flag is passed through. Set back to false if the
     * plan is ever downgraded, or every search fails with 402 again.
     */
    var PLAN_SUPPORTS_PERSONALIZED_QUERIES = true;

    // The same insights-js instance insights.js uses (RequireJS modules are singletons).
    var algoliaAnalytics = algoliaAnalyticsWrapper && algoliaAnalyticsWrapper.default;

    /*
     * The consent rule insights.js applies to its own cookie (useCookie()), made
     * available to every sender. Read at call time, so a visitor who accepts on
     * this page is tracked from that click onward.
     */
    function hasConsent(insights) {
        var cfg = (insights.config || window.algoliaConfig || {}).cookieConfiguration || {};

        if (!cfg.cookieRestrictionModeEnabled) {
            return true;
        }

        return !!algoliaCommon.getCookie(cfg.consentCookieName);
    }

    function onlyWithConsent(insights, method) {
        var original = insights[method];

        if (typeof original !== 'function') {
            return;
        }

        insights[method] = function () {
            if (!hasConsent(this)) {
                return undefined;
            }

            return original.apply(this, arguments);
        };
    }

    return function (insights) {
        /*
         * CONSENT. Upstream checks consent only in the autocomplete's own click
         * handler; trackView/trackClick/trackFilterClick/trackConversion send
         * regardless. Measured without consent: clicking a suggestion correctly
         * sent nothing, but the product page then sent "Viewed Product". All four
         * senders now require the same consent the click already did.
         */
        ['trackView', 'trackClick', 'trackFilterClick', 'trackConversion'].forEach(function (method) {
            onlyWithConsent(insights, method);
        });

        /*
         * ONE PROFILE ACROSS LOGIN. Upstream searches with the logged-in token
         * when there is one (authenticatedUserToken ?? userToken). Algolia's
         * Personalization is keyed on userToken ONLY and needs "the same
         * userToken for queries and events"; after login the events carry the
         * SAME anonymous userToken plus the new authenticatedUserToken, which is
         * how Algolia links the two histories (docs: sending-events/concepts/
         * usertoken). Searching with the authenticated token instead would start
         * every customer on an empty profile the moment they sign in. So search
         * with the anonymous token whenever the visitor has one.
         */
        if (algoliaAnalytics && typeof algoliaAnalytics.getUserToken === 'function') {
            insights.determineUserToken = function () {
                return algoliaAnalytics.getUserToken() || algoliaAnalytics.getAuthenticatedUserToken();
            };
        }

        var applyInsights = insights.applyInsightsToSearchParams;

        if (typeof applyInsights === 'function') {
            insights.applyInsightsToSearchParams = function () {
                var params = applyInsights.apply(this, arguments);

                if (!params) {
                    return params;
                }

                if (!PLAN_SUPPORTS_PERSONALIZED_QUERIES) {
                    delete params.enablePersonalization;
                }

                /*
                 * Before consent there is no token yet, and upstream then sends
                 * the literal string `userToken=undefined` — every such search
                 * counted as ONE user called "undefined" (and, with
                 * Personalization on, one shared profile). The autocomplete bakes
                 * its parameters in at page load, so the bogus value outlived the
                 * visitor's consent for the rest of the page. Send no token
                 * instead; from the next page after consent the real anonymous
                 * (or logged-in) token is used.
                 */
                var token = params.userToken;

                if (token === undefined || token === null || token === '' || token === 'undefined') {
                    delete params.userToken;
                    // No consent yet = no profile to personalize from; without a
                    // token Algolia would fall back to the visitor's IP address.
                    // Explicit false, not delete: the product indices carry
                    // enablePersonalization=true as their default (Advanced
                    // Personalization), so omitting it would still switch it on.
                    params.enablePersonalization = false;
                }

                return params;
            };
        }

        return insights;
    };
});
