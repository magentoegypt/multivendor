/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'Magento_Ui/js/grid/columns/thumbnail',
    'jquery',
    'mage/template',
    'text!Vnecoms_VendorsPriceComparison/template/grid/cells/thumbnail/preview.html',
    'Magento_Ui/js/modal/modal'
], function (Column, $, mageTemplate, thumbnailPreviewTemplate) {
    'use strict';

    return Column.extend({
        preview: function (row) {
            var modalHtml = mageTemplate(
                thumbnailPreviewTemplate,
                {
                    src: this.getOrigSrc(row), alt: this.getAlt(row), link: this.getLink(row),
                    linkText: $.mage.__('Go to Details Page')
                }
            );
            var previewPopup = $('<div/>').html(modalHtml);
            previewPopup.modal({
                title: this.getAlt(row),
                innerScroll: true,
                modalClass: '_image-box',
                buttons: []}).trigger('openModal');
        }
    });
});
