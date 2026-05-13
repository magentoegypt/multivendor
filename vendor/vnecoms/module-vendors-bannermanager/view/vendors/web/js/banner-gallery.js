/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint jquery:true*/

define([
    'jquery',
    'mage/template',
    'productGallery',
    'domReady!'
], function (jQuery, mageTemplate, productGallery) {
    "use strict";

    /**
     * Banner gallery widget
     */
    jQuery.widget('ves.bannerGallery', jQuery.mage.productGallery, {
        options: {
            imageSelector: '[data-role=image]',
            imageElementSelector: '[data-role=image-element]',
            template: '[data-template=image]',
            imageResolutionLabel: '[data-role=resolution]',
            imgTitleSelector: '[data-role=item_title]',
            imgStatusSelector: '[data-role=item_status]',
            imgUrlSelector: '[data-role=item_url]',
            imgShortDescriptionSelector: '[data-role=item_short_desc]',
            imageSizeLabel: '[data-role=size]',
        },

        /**
         * @inheritdoc
         */
        _create: function () {
            this._super();
        },

        /**
         * @inheritdoc
         */
        _bind: function () {
            this._on({
                updateItemTitle: '_updateItemTitle',
                updateItemStatus: '_updateItemStatus',
                updateItemUrl: '_updateItemUrl',
                updateItemShortDescription: '_updateItemShortDescription',
                openDialog: '_onOpenDialog',
                addItem: '_addItem',
                removeItem: '_removeItem',
                setImageType: '_setImageType',
                setPosition: '_setPosition',
                resort: '_resort',
                'mouseup [data-role=delete-button]': function (event) {
                    var $imageContainer;

                    event.preventDefault();
                    $imageContainer = jQuery(event.currentTarget).closest(this.options.imageSelector);
                    this.element.find('[data-role=dialog]').trigger('close');
                    this.element.trigger('removeItem', $imageContainer.data('imageData'));
                },
                // 'mouseup [data-role=make-base-button]': function (event) {
                //     var $imageContainer,
                //         imageData;
                //
                //     event.preventDefault();
                //     event.stopImmediatePropagation();
                //     $imageContainer = jQuery(event.currentTarget).closest(this.options.imageSelector);
                //     imageData = $imageContainer.data('imageData');
                //     this.setBase(imageData);
                // }
            });

            this.element.sortable({
                distance: 8,
                items: this.options.imageSelector,
                tolerance: 'pointer',
                cancel: 'input, button, .uploader',
                update: jQuery.proxy(function () {
                    this.element.trigger('resort');
                }, this)
            });
        },

        /**
         * Resort images
         * @private
         */
        _resort: function () {
            this._super();
        },

        /**
         * Set image position
         * @param event
         * @param data
         * @private
         */
        _setPosition: function (event, data) {
            var $element = this.findElement(data.imageData),
                curIndex = this.element.find(this.options.imageSelector).index($element),
                newPosition = data.position + (curIndex > data.position ? -1 : 0);

            if (data.position != curIndex) {
                if (data.position === 0) {
                    this.element.prepend($element);
                } else {
                    $element.insertAfter(
                        this.element.find(this.options.imageSelector).eq(newPosition)
                    );
                }
                this.element.trigger('resort');
            }

            this._contentUpdated();
        },

        /**
         * @overridedoc
         */
        _addItem: function (event, imageData) {
            var count = this.element.find(this.options.imageSelector).length,
                element,
                imgElement;

            imageData = jQuery.extend({
                'file_id': imageData.value_id ? imageData.value_id : Math.random().toString(33).substr(2, 18),
                'title': '',
                'item_url': '',
                'disabled': imageData.disabled ? imageData.disabled : 0,
                'position': count + 1,
                'item_status': 1
            }, imageData);

            element = this.imgTmpl({
                data: imageData
            });

            element = jQuery(element).data('imageData', imageData);

            if (count === 0) {
                element.prependTo(this.element);
            } else {
                element.insertAfter(this.element.find(this.options.imageSelector + ':last'));
            }

            /*if (!this.options.initialized &&
                this.options.images.length === 0 ||
                this.options.initialized &&
                this.element.find(this.options.imageSelector + ':not(.removed)').length === 1
            ) {
                this.setBase(imageData);
            }*/

            imgElement = element.find(this.options.imageElementSelector);

            imgElement.on('load', this._updateImageDimesions.bind(this, element));

            /*jQuery.each(this.options.types, jQuery.proxy(function (index, image) {
                if (imageData.file === image.value) {
                    this.element.trigger('setImageType', {
                        type: image.code,
                        imageData: imageData
                    });
                }
            }, this));*/

            //this._updateImagesRoles();
            this._contentUpdated();
        },


        /**
         *
         * @param {Object} imgData
         */
        _updateItemTitle: function (event, data) {
            var imageData = data.imageData,
                $imgContainer = this.findElement(imageData),
                $title = $imgContainer.find(this.options.imgTitleSelector),
                value;

            value = imageData.item_title;

            $title.val(value);

            this._contentUpdated();
        },

        /**
         *
         * @param {Object} imgData
         */
        _updateItemStatus: function(event, data) {
            var imageData = data.imageData,
                $imgContainer = this.findElement(imageData),
                $select = $imgContainer.find(this.options.imgStatusSelector),
                value;

            value = imageData.item_status;
            //console.log($select);
            $select.val(value);

            this._contentUpdated();
        },

        /**
         *
         * @param {Object} imgData
         */
        _updateItemUrl: function(event, data) {
            var imageData = data.imageData,
                $imgContainer = this.findElement(imageData),
                $input = $imgContainer.find(this.options.imgUrlSelector),
                value;

            value = imageData.item_url;

            $input.val(value);

            this._contentUpdated();
        },

        /**
         *
         * @param {Object} imgData
         */
        _updateItemShortDescription: function(event, data) {
            var imageData = data.imageData,
                $imgContainer = this.findElement(imageData),
                textarea = $imgContainer.find(this.options.imgShortDescriptionSelector),
                value;

            value = imageData.item_short_desc;

            textarea.val(value);

            this._contentUpdated();
        },

    });

    // Extension for ves.bannerGallery - Add dialog slide modal
    jQuery.widget('ves.bannerGallery', jQuery.ves.bannerGallery, {
        options: {
            dialogTemplate: '[data-role=img-dialog-tmpl]',
            dialogContainerTmpl: '[data-role=img-dialog-container-tmpl]'
        },

        _create: function () {
            var template = this.element.find(this.options.dialogTemplate),
                containerTmpl = this.element.find(this.options.dialogContainerTmpl);

            this._super();
            this.modalPopupInit = false;

            if (template.length) {
                this.dialogTmpl = mageTemplate(template.html().trim());
            }

            if (containerTmpl.length) {
                this.dialogContainerTmpl = mageTemplate(containerTmpl.html().trim());
            } else {
                this.dialogContainerTmpl = mageTemplate('');
            }

            this._initDialog();
        },

        /**
         * Bind handler to elements
         * @protected
         */
        _bind: function () {
            var events = {};

            this._super();

            events['click [data-role=close-panel]'] = jQuery.proxy(function () {
                this.element.find('[data-role=dialog]').trigger('close');
            }, this);
            events['click ' + this.options.imageSelector] = function (event) {
                if (!jQuery(event.currentTarget).is('.ui-sortable-helper')) {
                    jQuery(event.currentTarget).addClass('active');
                    var imageData = jQuery(event.currentTarget).data('imageData');
                    var $imageContainer = this.findElement(imageData);
                    if ($imageContainer.is('.removed')) {
                        return;
                    }
                    this.element.trigger('openDialog', [imageData]);
                }
            };
            this._on(events);
            this.element.on('sortstart', jQuery.proxy(function () {
                this.element.find('[data-role=dialog]').trigger('close');
            }, this));
        },


        /**
         * Initializes dialog element.
         */
        _initDialog: function () {
            var $dialog = jQuery(this.dialogContainerTmpl());

            $dialog.modal({
                'type': 'slide',
                title: jQuery.mage.__('Item Detail'),
                buttons: [],
                opened: function (e) {
                    $dialog.trigger('open');
                },
                closed: function (e) {
                    var target = jQuery(e.target);
                    var targetId = target.find('input[type="hidden"][name="file_id"]').val();
                    //$dialog.trigger('updateItemStatus', [$dialog.data('imageData')]);
                    $dialog.trigger('close');
                    //console.log(targetId);
                    var currentStatus = $dialog.data('imageData').item_status;
                    //console.log('status: '+ currentStatus);

                    if (currentStatus == 1) {
                        if (jQuery("#"+ targetId + " .item-status .i-status.disabled").length) {
                            jQuery("#"+ targetId + " .item-status .i-status.disabled").remove();
                        }

                        if (!jQuery("#"+ targetId + " .item-status .i-status.enabled").length) {
                            jQuery("#"+ targetId + " .item-status").append("<span class='i-status label-status enabled'>Enabled</span>");
                        }

                    } else {
                        if (jQuery("#"+ targetId + " .item-status .i-status.enabled").length) {
                            jQuery("#"+ targetId + " .item-status .i-status.enabled").remove();
                        }
                        if (!jQuery("#"+ targetId + " .item-status .i-status.disabled").length) {
                            jQuery("#"+ targetId + " .item-status").append("<span class='i-status label-status disabled'>Disabled</span>");
                        }
                    }
                    //console.log('item '+targetId+' updated!');

                }
            });

            $dialog.on('open', this.onDialogOpen.bind(this));
            $dialog.on('close', function () {
                var $imageContainer = $dialog.data('imageContainer');

                $imageContainer.removeClass('active');
                //$dialog.find('#hide-from-product-page').remove();
            });

            // update status change
            $dialog.on('change', '[data-role="item_title"]', function (e) {
                var target = jQuery(e.target),
                    targetName = target.attr('name'),
                    title = target.val(),
                    imageData = $dialog.data('imageData');

                this.element.find('input[type="hidden"][name="' + targetName + '"]').val(title);

                imageData.title = title;

                this.element.trigger('updateItemTitle', {
                    imageData: imageData
                });
            }.bind(this));


            $dialog.on('change', '[data-role="item_url"]', function (e) {
                var target = jQuery(e.target),
                    targetName = target.attr('name'),
                    url = target.val(),
                    imageData = $dialog.data('imageData');

                this.element.find('input[type="hidden"][name="' + targetName + '"]').val(url);

                imageData.item_url = url;

                this.element.trigger('updateItemUrl', {
                    imageData: imageData
                });
            }.bind(this));

            $dialog.on('change', '[data-role="item_short_desc"]', function (e) {
                var target = jQuery(e.target),
                    targetName = target.attr('name'),
                    short_desc = target.val(),
                    imageData = $dialog.data('imageData');

                this.element.find('input[type="hidden"][name="' + targetName + '"]').val(short_desc);

                imageData.item_short_description = short_desc;

                this.element.trigger('updateItemShortDescription', {
                    imageData: imageData
                });
            }.bind(this));


            $dialog.on('change', '[data-role="item_status"]', function (e) {
                var target = jQuery(e.target),
                    targetName = target.attr('name'),
                    item_status = target.val(),
                    imageData = $dialog.data('imageData');

                this.element.find('input[type="hidden"][name="' + targetName + '"]').val(item_status);

                imageData.item_status = item_status;

                this.element.trigger('updateItemStatus', {
                    imageData: imageData
                });

            }.bind(this));

            this.$dialog = $dialog;
        },

        _showDialog: function (imageData) {
            var $imageContainer = this.findElement(imageData),
                $template;

            $template = this.dialogTmpl({
                'data': imageData
            });

            this.$dialog
                .html($template)
                .data('imageData', imageData)
                .data('imageContainer', $imageContainer)
                .modal('openModal');
        },

        /**
         * Handles dialog open event.
         * @inheritdoc
         */
        onDialogOpen: function (event) {
            var imageData = this.$dialog.data('imageData'),image = document.createElement('img');

            //var widthField = this.$dialog.find('input[type=hidden][name*="item_width"]') ,heightField = this.$dialog.find('input[type=hidden][name*="item_height"]');
            image.src = imageData.url;

            if (imageData !== null && imageData.item_status !== null) {
                this.$dialog.find('[data-role="item_status"]').val(imageData.item_status);
                this.$dialog.find('input[type=hidden][name*="item_status"]').val(imageData.item_status);
            }

            /*if (this.$dialog.find('[data-role="item_width"]').val() == "") {
                this.$dialog.find('[data-role="item_width"]').val(image.width);
            }

            if (this.$dialog.find('[data-role="item_height"]').val() == "") {
                this.$dialog.find('[data-role="item_height"]').val(image.height);
            }*/


        },

        /**
         *
         * Click by image handler
         *
         * @param e
         * @param imageData
         * @private
         */
        _onOpenDialog: function (e, imageData) {
            if (imageData.media_type && imageData.media_type != 'image') {
                return;
            }
            this._showDialog(imageData);
        },


    });

    return jQuery.ves.bannerGallery;
});

