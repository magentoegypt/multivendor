/**
 * Vnecoms
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Vnecoms.com license sliderConfig is
 * available through the world-wide-web at this URL:
 * https://www.vnecoms.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Vnecoms
 * @package     Vnecoms_VendorsSellerList
 * @copyright   Copyright (c) 2017 Vnecoms (http://www.vnecoms.com/)
 * @license     https://www.vnecoms.com/LICENSE.txt
 */

define(
    [
        'jquery'
    ],
    function ($) {
        'use strict';

        return {
            /**
             * Start full page loader action
             */
            startLoader: function () {
                $('#ves_sellerlist_overlay').show();
            },

            /**
             * Stop full page loader action
             */
            stopLoader: function () {
                $('#ves_sellerlist_overlay').hide();
            }
        };
    }
);