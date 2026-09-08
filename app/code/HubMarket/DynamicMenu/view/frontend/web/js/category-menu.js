define([], function () {
    'use strict';

    return function (config, element) {
        var panel = element.querySelector('.hub-market-menu-panel');
        var trigger = element.querySelector('.hub-market-menu-trigger');

        element.addEventListener('toggle', function () {
            if (element.open && panel) {
                panel.scrollTop = 0;
            }
        });

        element.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && element.open) {
                element.open = false;

                if (trigger) {
                    trigger.focus();
                }
            }
        });
    };
});
