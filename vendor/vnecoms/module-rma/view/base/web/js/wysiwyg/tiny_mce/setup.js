/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* eslint-disable strict */
define([
    'jquery',
    'underscore',
    'wysiwygAdapter',
    'module',
    'mage/translate',
    'prototype',
    'mage/adminhtml/events',
    'mage/adminhtml/browser'
], function (jQuery, _, wysiwygAdapter, module) {
    var baseConfig = module.config().config || {},
    tinyMceWysiwygSetup = Class.create({
        wysiwygInstance: null
    });

    tinyMceWysiwygSetup.prototype = {

        /**
         * @param {*} htmlId
         * @param {Object} config
         */
        initialize: function (htmlId, config) {
            var WysiwygInstancePrototype = new wysiwygAdapter.getAdapterPrototype();

            _.bindAll(this, 'openFileBrowser');

            config = _.extend({}, baseConfig, config || {});
            this.wysiwygInstance = new WysiwygInstancePrototype(htmlId, config);
            this.wysiwygInstance.eventBus = this.eventBus = new window.varienEvents();
        },

        /**
         * @param {*} mode
         */
        setup: function (mode) {
            this.wysiwygInstance.setup(mode);
        },

        /**
         * @param {Object} o
         */
        openFileBrowser: function (o) {
            this.wysiwygInstance.openFileBrowser(o);
        },

        /**
         * @return {Boolean}
         */
        toggle: function () {
            return this.wysiwygInstance.toggle();
        },

        /**
         * On form validation.
         */
        onFormValidation: function () {
            this.wysiwygInstance.onFormValidation();
        },

        /**
         * Encodes the content so it can be inserted into the wysiwyg
         * @param {String} content - The content to be encoded
         *
         * @returns {*} - The encoded content
         */
        updateContent: function (content) {
            return this.wysiwygInstance.encodeContent(content);
        },
        turnOn: function(mode){
        	this.wysiwygInstance.turnOn(mode);
        }

    };
    window.tinyMceWysiwygSetup = tinyMceWysiwygSetup;

    return tinyMceWysiwygSetup;
});
