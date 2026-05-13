/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/lib/view/utils/async',
    'underscore',
    'ko',
    'Magento_Ui/js/form/element/abstract',
    'Magento_Variable/variables'
], function ($, _, ko, Abstract ) {
    'use strict';

    return Abstract.extend({
        defaults: {
            elementSelector: 'textarea',
            value: '',
            links: {
                value: '${ $.provider }:${ $.dataScope }'
            },
            template: 'ui/form/field',
            elementTmpl: 'ui/form/element/wysiwyg',
            content:        '',
            showSpinner:    false,
            loading:        false,
            imports: {
                loadHtmlContent: '${ $.parentName }.quick_reponse:value'
            }
        },

        /**
         *
         * @returns {} Chainable.
         */
        initialize: function () {
            this._super()
                .initNodeListener();

            return this;
        },

        /**
         *
         * @returns {exports}
         */
        initObservable: function () {
            this._super()
                .observe('value');

            return this;
        },
        /**
         * load Html content by quick reponse Id
         */
        loadHtmlContent: function(value){
            var _this = this;
            new Ajax.Request(this.ajax_url, {
                method:'post',
                parameters:{id: value},
                onSuccess: function(transport) {
                    var response = transport;
                    _this.value(response.responseText);
                    tinyMCE.execCommand('mceInsertContent',false,response.responseText);
                },
            });
        },
        /**
         *
         * @returns {} Chainable.
         */
        initNodeListener: function () {
            $.async({
                component: this,
                selector: this.elementSelector
            }, this.setElementNode.bind(this));

            return this;
        },

        /**
         *
         * @param {HTMLElement} node
         */
        setElementNode: function (node) {
            $(node).bindings({
                value: this.value
            });
        }
    });
});
