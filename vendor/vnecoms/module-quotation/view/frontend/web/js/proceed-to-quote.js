/**
 * Copyright © 2017 Vnecoms, Inc. All rights reserved.
 * See COPYING.txt for license details.
 *
 * In Cart page
 */

define([
        'jquery'
    ],
    function ($) {
        'use strict';

        return function (config, element) {
            $(element).click(function (event) {
                event.preventDefault();
                $(config.modalId).modal('openModal');
            });
        };
    }
);
