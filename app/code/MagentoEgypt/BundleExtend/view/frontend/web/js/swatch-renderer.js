define([
    'jquery',
    'Magento_Swatches/js/swatch-renderer'
] , function ($) {
    'use strict';

    $.widget('bundle.SwatchRenderer', $.mage.SwatchRenderer, {
        /**
         * Update [gallery-placeholder] or [product-image-photo]
         * @param {Array} images
         * @param {jQuery} context
         * @param {Boolean} isInProductView
         */
        updateBaseImage: function (images, context, isInProductView) {
            var justAnImage = images[0];
            if (justAnImage && justAnImage.img) {
                this.element.closest('.product-item-info').find('.product-image-photo').attr('src', justAnImage.img);
            }
        }
    });

    return $.bundle.SwatchRenderer;
});