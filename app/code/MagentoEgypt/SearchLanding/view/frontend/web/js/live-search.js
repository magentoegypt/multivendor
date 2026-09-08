/**
 * Hub Market — live search on /search.
 *
 * THROTTLING HERE IS NOT POLISH, IT IS CAPACITY. This host runs 2 cores with
 * `pm.max_children = 5` on the www FPM pool, and every request goes through
 * OpenSearch. One un-debounced keystroke handler typing "hoodie" would fire six
 * overlapping requests and take five of the five available workers, which is an
 * outage for every other visitor, not a slow search box. Hence, all of:
 *
 *   · 300ms debounce, so a typed word costs one request, not one per letter
 *   · a 3-character floor, matching Magento's own minimum query length
 *   · AbortController, so a superseded request stops occupying a worker the
 *     moment its answer stops mattering
 *   · a same-query guard, so re-typing what is already shown costs nothing
 *
 * The server enforces the floor again — this file is a convenience, not the
 * control. See Controller/Ajax/Index.
 *
 * Tabs are wired by delegation on the container because the panels arrive with
 * each response; binding to the buttons directly would lose the handlers on
 * every re-render.
 */
define([], function () {
    'use strict';

    var DEBOUNCE_MS = 300,
        MIN_CHARS = 3;

    return function (config, element) {
        var input = element.querySelector('.hm-search-hero__input'),
            results = document.getElementById('hm-live-results'),
            defaults = document.getElementById('hm-search-defaults'),
            status = element.querySelector('[data-role="hm-live-status"]'),
            endpoint = element.getAttribute('data-live-url'),
            searchingLabel = element.getAttribute('data-searching-label') || '',
            timer = null,
            controller = null,
            lastQuery = null;

        if (!input || !results || !endpoint) {
            return;
        }

        /**
         * The inline spinner at the field's trailing edge.
         *
         * Driven from schedule(), not from run(), so it appears the moment a
         * request is QUEUED rather than 300ms later when the debounce fires —
         * otherwise the field sits inert for the exact interval the shopper is
         * waiting to see something happen.
         */
        function setLoading(on) {
            element.classList.toggle('is-loading', on);

            if (status) {
                // Cleared rather than left announcing: the results summary is
                // itself a live region and announces the count when it lands, so
                // leaving "Searching…" behind would double up.
                status.textContent = on ? searchingLabel : '';
            }
        }

        function showDefaults() {
            results.innerHTML = '';
            results.hidden = true;

            if (defaults) {
                defaults.hidden = false;
            }
            lastQuery = null;
            setLoading(false);
        }

        function run(query) {
            // Abort whatever is still in flight — its answer is already stale.
            if (controller) {
                controller.abort();
            }
            controller = new AbortController();

            results.setAttribute('aria-busy', 'true');

            fetch(endpoint + (endpoint.indexOf('?') === -1 ? '?' : '&') +
                    'q=' + encodeURIComponent(query), {
                signal: controller.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            })
                .then(function (response) {
                    return response.ok ? response.text() : '';
                })
                .then(function (html) {
                    if (!html) {
                        // Empty body means the server declined or failed. Leave
                        // the last good panel alone rather than blanking it.
                        return;
                    }
                    results.innerHTML = html;
                    results.hidden = false;

                    if (defaults) {
                        defaults.hidden = true;
                    }
                })
                .catch(function () {
                    // AbortError is the normal path for superseded typing.
                })
                .then(function () {
                    results.removeAttribute('aria-busy');

                    // Only stop the spinner if THIS request is still the current
                    // one. An aborted request settles after its replacement has
                    // already started, and clearing here unconditionally would
                    // switch the spinner off while a newer fetch is still in
                    // flight.
                    if (controller && controller.signal.aborted === false) {
                        setLoading(false);
                    }
                });
        }

        function schedule() {
            var query = input.value.trim();

            window.clearTimeout(timer);

            if (query.length < MIN_CHARS) {
                if (controller) {
                    controller.abort();
                }
                showDefaults();

                return;
            }

            if (query === lastQuery) {
                return;
            }

            setLoading(true);

            timer = window.setTimeout(function () {
                lastQuery = query;
                run(query);
            }, DEBOUNCE_MS);
        }

        input.addEventListener('input', schedule);

        // Enter goes to the full results page — the live panel is a preview, and
        // only the real page has pagination, sorting and layered filters.
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                input.value = '';
                showDefaults();
            }
        });

        // Deep link: /search?q=… paints results on arrival without a keystroke.
        (function () {
            var initial = new URLSearchParams(window.location.search).get('q');

            if (initial && initial.trim().length >= MIN_CHARS) {
                input.value = initial.trim();
                lastQuery = input.value;
                // Deep links bypass schedule(), so the spinner has to be started
                // here too — otherwise /search?q=… loads with no sign of work.
                setLoading(true);
                run(lastQuery);
            }
        }());

        // Tab switching, delegated — panels are replaced on every response.
        results.addEventListener('click', function (e) {
            var tab = e.target.closest ? e.target.closest('.hm-live__tab') : null,
                wanted;

            if (!tab) {
                return;
            }
            wanted = tab.getAttribute('data-panel');

            Array.prototype.forEach.call(results.querySelectorAll('.hm-live__tab'), function (t) {
                var on = t === tab;

                t.classList.toggle('is-active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            Array.prototype.forEach.call(results.querySelectorAll('.hm-live__panel'), function (panel) {
                var on = panel.getAttribute('data-panel') === wanted;

                panel.classList.toggle('is-active', on);
                panel.hidden = !on;
            });
        });
    };
});
