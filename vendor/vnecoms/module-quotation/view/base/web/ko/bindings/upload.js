/*
 * Copyright © 2017 Vnecoms. All rights reserved.
 */
define([
    'ko',
    'Magento_Ui/js/lib/knockout/template/renderer',
    'jquery',
    'Magento_Ui/js/lib/view/utils/async',
    'uiRegistry',
    'underscore',
    'jquery/file-uploader',
    'jquery/ui'
], function (ko, renderer, $, async, registry, _) {
    'use strict';
    var defaults = {
            dataType: 'json',
            sequentialUploads: true,
            formData: {
                'form_key': window.FORM_KEY
            }
    };

    ko.bindingHandlers.upload = {
        init: function (element, valueAccessor, allBindings, viewModel) {
            // var config = valueAccessor();
            // _.extend(config, defaults);
            // $(element).fileupload({
            //     url: '',
            //     dataType: 'json',
            //     autoUpload: false,
            //     acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
            //     maxFileSize: 999000,
            //     // Enable image resizing, except for Android and Opera,
            //     // which actually support image resizing, but fail to
            //     // send Blob objects via XHR requests:
            //     disableImageResize: /Android(?!.*Chrome)|Opera/
            //         .test(window.navigator.userAgent),
            //     previewMaxWidth: 100,
            //     previewMaxHeight: 100,
            //     previewCrop: true
            // });

            // This will be called when the binding is first applied to an element
            // Set up any initial state, event handlers, etc. here

            var value = ko.unwrap(valueAccessor());
            _.extend(value, defaults);

            console.log(value);
            $(element).fileupload({
                    url: '',
                    dataType: 'json',
                    autoUpload: false,
                    acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
                    maxFileSize: 999000,
                    // Enable image resizing, except for Android and Opera,
                    // which actually support image resizing, but fail to
                    // send Blob objects via XHR requests:
                    disableImageResize: /Android(?!.*Chrome)|Opera/
                        .test(window.navigator.userAgent),
                    previewMaxWidth: 100,
                    previewMaxHeight: 100,
                    previewCrop: true
            });
        },

        /**
         * Reads params passed to binding.
         * Set html to node element, apply bindings and call magento attributes parser.
         *
         * @param {HTMLElement} el - Element to apply bindings to.
         * @param {Function} valueAccessor - Function that returns value, passed to binding.
         * @param {Object} allBindings - Object, which represents all bindings applied to element.
         * @param {Object} viewModel - Object, which represents view model binded to el.
         * @param {ko.bindingContext} bindingContext - Instance of ko.bindingContext, passed to binding initially.
         */
        update: function (el, valueAccessor, allBindings, viewModel, bindingContext) {

        }
    };

    renderer.addAttribute('upload');

});