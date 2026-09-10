/**
 * Hub Market — the department chips on the bundles landing page.
 *
 * The chips and the cards are both rendered by PHP; this only wires one to the
 * other. It reveals the row first, so a page whose JavaScript never arrives
 * shows the full grid instead of a row of buttons that do nothing.
 *
 * Filtering is `hidden` on the list item, not a class: nothing in the sheet sets
 * `display` on `.hm-bundles__item`, so the user-agent rule applies, and the
 * item leaves the grid flow entirely rather than collapsing to a gap.
 */
define([], function () {
    'use strict';

    return function (config, element) {
        var list = document.querySelector('.hm-bundles__list');

        if (!list || !element) {
            return;
        }

        element.hidden = false;

        element.addEventListener('click', function (event) {
            var chip = event.target.closest('.hm-bundle-chip'),
                wanted;

            if (!chip) {
                return;
            }

            wanted = chip.getAttribute('data-hm-cat');

            Array.prototype.forEach.call(element.querySelectorAll('.hm-bundle-chip'), function (other) {
                var on = other === chip;

                other.classList.toggle('hm-bundle-chip--on', on);
                other.setAttribute('aria-pressed', on ? 'true' : 'false');
            });

            Array.prototype.forEach.call(list.querySelectorAll('.hm-bundles__item'), function (item) {
                var cats = (item.getAttribute('data-hm-cats') || '').split(' ');

                item.hidden = wanted !== 'all' && cats.indexOf(wanted) === -1;
            });
        });
    };
});
