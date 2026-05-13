/**
 * Copyright Â© 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/modal',
    'jquery/ui',
    'prototype'
], function (jQuery, $t) {

    jQuery.widget('mage.modal', jQuery.mage.modal, {

        /**
         *
         */
        openModal: function () {
            this.options.isOpen = true;
            this.focussedElement = document.activeElement;
            this._createOverlay();
            this._setActive();
            this._setKeyListener();
            this.modal.one(this.options.transitionEvent, _.bind(this._setFocus, this, 'end', 'opened'));
            this.modal.one(this.options.transitionEvent, _.bind(this._trigger, this, 'opened'));
            this.modal.addClass(this.options.modalVisibleClass + ' block-static-modals');

            if (!this.options.transitionEvent) {
                this._trigger('opened');
            }

            return this.element;
        },

        /**
         * Destroy overlay.
         */
        _destroyOverlay: function () {
            if (this._getVisibleCount()) {
                this.overlay.unbind().on('click', this.prevOverlayHandler);
            } else {
                jQuery(this.options.appendTo).removeClass(this.options.parentModalClass);
                if( this.overlay) {
                    this.overlay.remove();
                    this.overlay = null;
                }
            }
        }
    });


    window.VendorsCmsBlockPlugin = {};
    window.Blocks = {
        textareaElementId: null,
        blocksContent: null,
        dialogWindow: null,
        dialogWindowId: 'blocks-chooser',
        overlayShowEffectOptions: null,
        overlayHideEffectOptions: null,
        insertFunction: 'Blocks.insertBlock',
        init: function (textareaElementId, insertFunction) {
            if ($(textareaElementId)) {
                this.textareaElementId = textareaElementId;
            }
            if (insertFunction) {
                this.insertFunction = insertFunction;
            }
        },

        resetData: function () {
            this.blocksContent = null;
            this.dialogWindow = null;
        },

        openBlockChooser: function (blocks) {
            this.blocksContent = blocks;
            if (this.blocksContent) {
                this.openDialogWindow(this.blocksContent);
            }
        },
        openDialogWindow: function (blocksContent) {
            var windowId = this.dialogWindowId;
            jQuery('<div id="' + windowId + '">' + Blocks.blocksContent + '</div>').modal({
                title: $t('Insert Static Block...'),
                type: 'slide',
                buttons: [],
                closed: function (e, modal) {
                    modal.modal.remove();
                }
            });
            jQuery('#' + windowId).modal('openModal');
            blocksContent.evalScripts.bind(blocksContent).defer();
        },
        closeDialogWindow: function () {
            jQuery('#' + this.dialogWindowId).modal('closeModal');
        },
        prepareBlockRow: function (varValue, varLabel) {
            var value = (varValue).replace(/"/g, '&quot;').replace(/'/g, '\\&#39;');
            var content = '<a href="#" onclick="'+this.insertFunction+'(\''+ value +'\');return false;">' + varLabel + '</a>';
            return content;
        },
        insertBlock: function (value) {
            var windowId = this.dialogWindowId;
            jQuery('#' + windowId).modal('closeModal');
            var textareaElm = $(this.textareaElementId);
            if (textareaElm) {
                var scrollPos = textareaElm.scrollTop;
                updateElementAtCursor(textareaElm, value);
                textareaElm.focus();
                textareaElm.scrollTop = scrollPos;
                jQuery(textareaElm).change();
                textareaElm = null;
            }
            return;
        }
    };


    window.VendorsCmsBlockPlugin = {
        editor: null,
        blocks: null,
        textareaId: null,

        setEditor: function (editor) {
            this.editor = editor;
        },

        loadChooser: function (url, textareaId) {
            this.textareaId = textareaId;

            if (this.blocks == null) {
                new Ajax.Request(url, {
                    parameters: {},
                    onComplete: function (transport) {
                        if (transport.responseText) {
                            Blocks.init(this.textareaId, 'VendorsCmsBlockPlugin.insertBlock', this.editor);
                            this.blocks = transport.responseText;
                            this.openChooser(this.blocks);
                        }
                    }.bind(this)
                });
            } else {
                this.openChooser(this.blocks);
            }
            return;
        },

        openChooser: function (blocks) {
            Blocks.openBlockChooser(blocks);
        },

        insertBlock : function (value) {
            var valueFinal = '{{block block_id="'+value+'"}}';
            if (this.textareaId) {
                Blocks.init(this.textareaId);
                Blocks.insertBlock(valueFinal);
            } else {
                Blocks.closeDialogWindow();
                this.editor.execCommand('mceInsertContent', false, valueFinal);
            }
            return;
        }
    };

    return jQuery.mage.modal;

});