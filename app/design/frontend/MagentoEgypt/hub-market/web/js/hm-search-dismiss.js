/**
 * Hub Market: tapping outside the Algolia search suggestions closes them (ClickUp 14zb93nuunn item 1).
 *
 * The Algolia extension runs autocomplete-js with `debug: algoliaCommon.isMobile()` on
 * purpose (autocomplete.js: "to be able to remove keyboard and be able to scroll"). Debug mode
 * also keeps the panel open when the input loses focus, so on a phone nothing closed it: the
 * panel had no close control and ignored taps outside it. This closes it the way the keyboard
 * would, with Escape, which in autocomplete-core closes an open panel and keeps the query.
 *
 * It only runs where Algolia turns debug mode on (the same mobile-UA-or-touch test), so the
 * desktop behaviour is untouched. The dismissing tap is swallowed rather than also following
 * whatever link sat under the finger, which is how any overlay dismissal should behave.
 */
(function () {
    'use strict';

    var isMobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i.test(navigator.userAgent)
        || 'ontouchstart' in window || navigator.maxTouchPoints > 0;

    if (!isMobile) {
        return;
    }

    function openPanel() {
        return document.querySelector('.aa-Panel');
    }

    function isInsideSearch(target) {
        return !!(target && target.closest && target.closest('.aa-Panel, .aa-Autocomplete, .aa-Form, .block-search'));
    }

    function close() {
        var input = document.querySelector('.aa-Input');

        if (input) {
            input.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape', code: 'Escape', keyCode: 27, bubbles: true}));
            input.blur();
        }
    }

    document.addEventListener('click', function (event) {
        if (!openPanel() || isInsideSearch(event.target)) {
            return;
        }
        event.preventDefault();
        event.stopPropagation();
        close();
    }, true);
}());
