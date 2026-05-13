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
        'jquery',
        'mage/storage',
        'Vnecoms_VendorsSellerList/js/model/loader'
    ],
    function ($, storage, loader) {
        'use strict';

        return function (submitUrl) {
            /** save active state */
            //var actives = [];
            /*$('.ln-filter-options-item').each(function (index) {
                if ($(this).hasClass('active')) {
                    actives.push($(this).attr('attribute'));
                }
            });*/
            //window.layerActiveTabs = actives;

            /** start loader */
            loader.startLoader();

            /** change browser url */
            if (typeof window.history.pushState === 'function') {
                window.history.pushState({url: submitUrl}, '', submitUrl);
            }

            return storage.post(submitUrl, {}).done(
                function (response) {
                    if (response.backUrl) {
                        window.location = response.backUrl;
                        return;
                    }
                    /*if (response.navigation) {
                        $('#layered-filter-block-container').replaceWith(response.navigation);
                        $('#layered-filter-block-container').trigger('contentUpdated');
                    }*/
                    if (response.sellerslist) {
                        $('#seller-list-ajax').replaceWith(response.sellerlist);
                        $('#seller-list-ajax').trigger('contentUpdated');
                    }
                }
            ).fail(
                function () {
                    window.location.reload();
                }
            ).always(
                function () {
                    loader.stopLoader();
                }
            );
        };
    }
);
