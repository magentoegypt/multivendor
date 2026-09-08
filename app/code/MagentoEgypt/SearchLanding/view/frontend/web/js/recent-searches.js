/**
 * Hub Market — recent searches, per visitor.
 *
 * There is no server side to this. Magento stores search terms globally
 * (search_query, which is what Trending Searches reads) but keeps no per-visitor
 * history, and inventing one would mean writing a customer's search log to the
 * database — a privacy cost well out of proportion to a convenience list. So it
 * lives in localStorage on the visitor's own device and never leaves it.
 *
 * Two modes, one file:
 *   record — mounted on the search RESULTS page. Reads ?q= and stores it.
 *   render — mounted on /search. Paints the list, or leaves the section hidden.
 *
 * Recording from the RESULT page rather than from a form submit is deliberate: it
 * catches every route into search, including a trending pill and a bookmarked
 * result URL, neither of which goes through a form on this site.
 */
define([], function () {
    'use strict';

    var KEY = 'hm-recent-searches',
        MAX = 5;

    /**
     * localStorage throws in Safari private mode and when a browser blocks
     * storage entirely. A convenience list must never break the page it sits on,
     * so every access is guarded and simply yields "no history" on failure.
     */
    function read() {
        try {
            var raw = window.localStorage.getItem(KEY);
            if (!raw) {
                return [];
            }
            var parsed = JSON.parse(raw);

            return Array.isArray(parsed) ? parsed.filter(function (t) {
                return typeof t === 'string' && t.length > 0;
            }) : [];
        } catch (e) {
            return [];
        }
    }

    function write(list) {
        try {
            window.localStorage.setItem(KEY, JSON.stringify(list));
        } catch (e) {
            // Storage full or blocked — the list is expendable, the page is not.
        }
    }

    function record() {
        var q = new URLSearchParams(window.location.search).get('q'),
            list;

        if (!q) {
            return;
        }
        q = q.trim();

        if (!q) {
            return;
        }

        list = read().filter(function (t) {
            // Case-insensitive dedupe, so "Bag" does not sit above "bag".
            return t.toLowerCase() !== q.toLowerCase();
        });
        list.unshift(q);
        write(list.slice(0, MAX));
    }

    function render(element) {
        var list = read(),
            ul = element.querySelector('#hm-recent-list'),
            searchUrl;

        if (!ul || !list.length) {
            // Section stays hidden. An empty "Recent Searches" heading is worse
            // than no section at all — it reads as something that failed to load.
            return;
        }

        searchUrl = ul.getAttribute('data-search-url') || '';

        list.forEach(function (term) {
            var li = document.createElement('li'),
                a = document.createElement('a'),
                icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg'),
                use = document.createElementNS('http://www.w3.org/2000/svg', 'use');

            li.className = 'hm-search-recent__item';

            a.className = 'hm-search-recent__link';
            // Built with URL/searchParams so the term is encoded properly —
            // Arabic queries and "&" both go through this path.
            a.href = searchUrl + (searchUrl.indexOf('?') === -1 ? '?' : '&') +
                'q=' + encodeURIComponent(term);

            icon.setAttribute('class', 'hm-icon hm-icon--sm hm-search-recent__icon');
            icon.setAttribute('aria-hidden', 'true');
            icon.setAttribute('focusable', 'false');
            use.setAttribute('href', '#hm-clock');
            icon.appendChild(use);

            a.appendChild(icon);
            // textContent, never innerHTML: this string came from a URL the
            // visitor can edit, so it is untrusted input.
            a.appendChild(document.createTextNode(term));

            li.appendChild(a);
            ul.appendChild(li);
        });

        element.hidden = false;
    }

    return function (config, element) {
        if (config && config.mode === 'record') {
            record();

            return;
        }
        render(element);
    };
});
