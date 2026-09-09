/**
 * Hub Market — the price filter's caption, in the prototype's words.
 *
 * The prototype's control is a single `<input type="range" min="0" max="2000">`
 * captioned at both ends: the floor on the left, "Up to $2000" on the right.
 * Mageplaza renders a jQuery UI RANGE slider and writes ONE string into
 * `#ln_slider_text_<code>` — "EGP 11 - EGP 935". The lower handle is hidden in
 * CSS so the control already reads as a maximum; this splits the caption to
 * match, and keeps it in step as the handle moves.
 *
 * Reading the string rather than the slider's own values is deliberate: the
 * numbers arrive already formatted in the store's currency and locale, which is
 * work this module has no business repeating (and would get wrong for ar_SA).
 *
 * The floor shown on the left is the collection's real minimum, not a hard
 * zero. It is what the hidden handle is actually sitting on, so it is the
 * honest label — quoting "EGP 0" for a rail whose cheapest item is EGP 11 would
 * be decoration.
 */
define([], function () {
    'use strict';

    var SPLIT = /\s+-\s+/;

    return function (config, element) {
        var host = element || document;

        //  Mageplaza builds the slider after page load, so the caption is not
        //  in the DOM when this runs. Poll briefly rather than bind to one of
        //  their internal events — the same approach hm-configurable-options.js
        //  takes with the swatch renderer.
        (function wait(tries) {
            var source = host.querySelector('[id^="ln_slider_text_"]');

            if (source) {
                start(source);
                return;
            }
            if (tries < 60) {
                setTimeout(function () { wait(tries + 1); }, 150);
            }
        }(0));

        function start(source) {
        var rendered = null,
            low = document.createElement('span'),
            high = document.createElement('span'),
            observer;

        low.className = 'hm-price-range__floor';
        high.className = 'hm-price-range__cap';

        /**
         * Mageplaza rewrites the element's text on every slide, which would
         * throw away any children we put in it. So the original string stays
         * where it is — hidden — and the two captions live beside it.
         */
        function mount() {
            var holder = document.createElement('div');

            holder.className = 'hm-price-range';
            holder.appendChild(low);
            holder.appendChild(high);
            source.parentNode.insertBefore(holder, source.nextSibling);
            source.classList.add('hm-price-range__raw');
        }

        function paint() {
            //  &nbsp; between currency and number: normalise before splitting.
            var text = (source.textContent || '').replace(/ /g, ' ').trim(),
                parts;

            if (text === rendered) {
                return;
            }
            rendered = text;
            parts = text.split(SPLIT);

            if (parts.length !== 2) {
                //  A shape this module does not understand — leave Mageplaza's
                //  own caption visible rather than showing nothing.
                source.classList.remove('hm-price-range__raw');
                return;
            }

            source.classList.add('hm-price-range__raw');
            low.textContent = parts[0];
            high.textContent = (config && config.capLabel ? config.capLabel : 'Up to %1')
                .replace('%1', parts[1]);
        }

        mount();
        paint();

        if (window.MutationObserver) {
            observer = new MutationObserver(paint);
            observer.observe(source, { characterData: true, childList: true, subtree: true });
        } else {
            setInterval(paint, 400);
        }
        }
    };
});
