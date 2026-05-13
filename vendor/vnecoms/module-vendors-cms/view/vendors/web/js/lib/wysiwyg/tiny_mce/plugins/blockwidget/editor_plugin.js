/**
 * Copyright © Vnecoms, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* global tinymce, varienGlobalEvents, Base64 */
/* eslint-disable strict */
define([
    'jquery',
    'wysiwygAdapter'
], function (jQuery, wysiwyg) {
    return function (config) {
        tinymce.create('tinymce.plugins.VendorsCmsBlockPlugin', {
            /**
             * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
             * @param {string} url Absolute URL to where the plugin is located.
             */
            init : function (ed, url) {
                
                ed.addCommand('mceVendorsCmsBlock', function() {
                    //var pluginSettings = ed.settings.magentoPluginsOptions.get('blockwidget');
                    VendorsCmsBlockPlugin.setEditor(ed);
                    VendorsCmsBlockPlugin.loadChooser(config.url, ed.getElement().id);
                });

                // Register Widget plugin button
                ed.addButton('blockwidget', {
                    title : jQuery.mage.__('Insert Block'),
                    cmd : 'mceVendorsCmsBlock',
                    image : url + '/img/grid.jpg'
                });

            },


            /**
             * @return {Object}
             */
            getInfo : function () {
                return {
                    longname : 'Vendors Insert Block Widget Manager Plugin for TinyMCE 4.x',
                    author : 'Ves Team',
                    authorurl : 'http://www.vnecoms.com',
                    infourl : 'http://www.vnecoms.com',
                    version : "1.0"
                };
            }
        });

        // Register plugin
        tinymce.PluginManager.add('blockwidget', tinymce.plugins.VendorsCmsBlockPlugin);
    };
});

