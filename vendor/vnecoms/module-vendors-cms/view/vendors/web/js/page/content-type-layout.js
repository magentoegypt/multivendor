
define([
    'jquery',
    'mage/template',
    'Magento_Ui/js/modal/confirm',
    'jquery/ui',
    'useDefault',
    'collapsable',
    'mage/translate',
    'mage/backend/validation',
    'Magento_Ui/js/modal/modal'
], function ($, mageTemplate, confirm) {
    'use strict';

    $.widget('mage.contenttypeLayout', {
        item: {
            'group' : { 'count' : 0},
            'field' : { 'count' : 0},
            'block' : { 'count' : 0},
            'template' : '#contenttype-content-layout-item-'
        },

        _create: function () {
            // Initialization
            this.prevLayoutVal = '';
            this._initLayoutItems();
            this._initLayouts();

            // Up-to-date
            $('#contenttype_layout').trigger('change');
        },

        _addValidation: function () {
            $.validator.addMethod(
                // Add some validator here
            );
        },

        /**
         * Init the drag and drop system
         */
        _initDragAndDropLayout: function () {
            var widget = this;

            // Drag and drop system
            $('.column-dropable').sortable({
                items: '> .column-draggable',
                handle: '> .column-handler',
                connectWith: '.column-dropable',
                placeholder: 'sortable-placeholder',
                scroll: true,
                start: function (event, ui) {
                    $(event.target).addClass('currently-dragging');
                },
                receive: function (event, ui) {
                    if (ui.item.hasClass('layout-item')) {
                        var item = ui.item.attr('id').replace('layout-item-', '');
                        item = widget.getItemTemplate(event, item);

                        ui.item.replaceWith(item);
                    } // Update the column and parent id values
                    else if (ui.item.hasClass('grid-layout-item')) {
                        var parentItem = ui.item.parent(),
                            columnId = '',
                            parentId = '';

                        // Check if the direct parent is a column or an item
                        if (parentItem.hasClass('column')) {
                            columnId = parentItem.attr('id').replace('col', '');
                        } else {
                            columnId = parentItem.parent().find('.input_column').val();
                            parentId = parentItem.attr('rel');
                        }

                        ui.item.find('.input_column').val(columnId);
                        ui.item.find('.input_parent_id').val(parentId);
                    }
                },
                stop: function (event, ui) {
                    $(event.target).removeClass('currently-dragging');


                    // Update Drag & Drop and positions
                    widget._initLayoutItems();
                    widget._initDragAndDropLayout();
                    widget._updateItemPositions();
                },
            });
        },

        /**
         * Init the layout items
         */
        _initLayoutItems: function () {
            var dnd = mageTemplate('#contenttype-content-layout-items');

            this.element.find('.field-contenttype-layout-items').html('');
            this.element.find('.field-contenttype-layout-items').append(dnd);

            /**
             * Update field item form
             */
            var updateFieldType = function (event, data) {
                data = data || {};
                var currentElement = $(event.target),
                    fieldId = currentElement.val(),
                    fieldType = currentElement.children('[value="' + fieldId + '"]').attr('type'),
                    fieldForm = currentElement.closest('.grid-layout-item-alt').children('.layout-field-format'),
                    tempId = currentElement.closest('.grid-layout-item-alt').find('[name^="layout[item][field]"][name$="[temp_id]"]').val(),
                    exists,
                    tmpl;

                fieldForm.children().addClass('ignore-validate').hide();

                // Update the title
                var title = currentElement.find('option:selected').text();
                currentElement.closest('.grid-layout-item-field').find('.grid-layout-item-title .field-title').text(title);

                // If field type and his template exists
                if (fieldType) {
                    tmpl = '#contenttype-content-layout-item-field-' + fieldType;
                    data.id = tempId;
                    exists = fieldForm.find(tmpl);

                    if (exists.length) {
                        exists.removeClass('ignore-validate').show();
                    } else {
                        tmpl = mageTemplate(tmpl, {
                            data: data
                        });
                        if (tmpl) {
                            fieldForm.append(tmpl);
                        }
                    }
                }

            }

            /**
             * Update the group label on change
             */
            var syncGroupLabel = function (event) {
                var currentElement = $(event.target);
                currentElement.closest('.grid-layout-item-group').find('.grid-layout-item-title .group-label').text(currentElement.val());
            };

            this._on({
                /**
                 * Update field item form
                 */
                'change select[class=layout_field_custom_field_id]': updateFieldType,

                /**
                 * Update the group label on change
                 */
                'change .layout_group_html_name': syncGroupLabel,
                'keyup .layout_group_html_name': syncGroupLabel,
                'paste .layout_group_html_name': syncGroupLabel,
            });

            $('.layout_group_html_name').trigger('change');
        },

        _initLayouts: function () {

            /**
             * Update the preview image to the selected layout
             */
            var updatePreviewImage = function (layoutSelect, layoutValue) {
                var imageBlock = layoutSelect.parent().find('.preview > #content_layout-img'),
                    regex = new RegExp("layout_([0-9]*)", "g"),
                    newSrc = imageBlock.attr('src').replace(regex, 'layout_' + layoutValue);

                imageBlock.attr('src', newSrc);
            };

            /**
             * Update the template grid to the selected layout
             */
            var updateTemplateLayout = function (layoutValue, widget, data) {
                data = data || {};
                var container = $('#contenttype-layout-grid'),
                    disabledBlock = container.find('#layout_' + layoutValue),
                    previousBlock = container.find('#layout_' + widget.prevLayoutVal),
                    tmpl;

                if (previousBlock.length) {
                    previousBlock.addClass('ignore-validate').hide();
                }

                if (disabledBlock.length) {
                    disabledBlock.removeClass('ignore-validate').show();
                } else {
                    tmpl = widget.element.find('#layouts > #contenttype-content-layout-columns-' + layoutValue).html();
                    tmpl = mageTemplate(tmpl, {
                        data: data
                    });

                    // Append and init layout
                    $(tmpl).appendTo(container);
                    widget._initDragAndDropLayout();
                }

                // Update layout items
                updateLayoutItems(layoutValue, widget);

                widget.prevLayoutVal = layoutValue;
            };

            /**
             * Set the previous layout items on the current layout
             */
            var updateLayoutItems = function (layoutValue, widget) {
                var container = $('#contenttype-layout-grid'),
                    currentBlock = container.find('#layout_' + layoutValue),
                    previousBlock = container.find('#layout_' + widget.prevLayoutVal),
                    items = previousBlock.find('.grid-layout-item');

                if (layoutValue == 0) {
                    return null;
                }

                items.each(function (index) {
                    var item = $(this).detach(),
                        col = item.find('.input_column').val(),
                        receiver = currentBlock.find('#col' + col);

                    if (receiver.length) {
                        receiver.append(item);
                    } else {
                        item.find('.input_column').val(1);
                        currentBlock.find('#col1').append(item);
                    }
                });
            }

            /**
             * Set the info message if it's a custom layout
             */
            var updateMessageCustomLayout = function (layoutSelect, layoutValue) {
                var warning = layoutSelect.parent().find('#content_layout-warning');

                if (layoutValue == '0') {
                    warning.show();
                } else {
                    warning.hide();
                }
            };

            /**
             * Disable next steps if it's a custom layout
             */
            var updateStepsCustomLayout = function (layoutValue) {
                var stepItems = $('.layout-manager-items'),
                    stepGrid = $('.layout-manager-grid');

                if (layoutValue != '0') {
                    stepItems.removeClass('ignore-validate').show();
                    stepGrid.removeClass('ignore-validate').show();
                } else {
                    stepItems.addClass('ignore-validate').hide();
                    stepGrid.addClass('ignore-validate').hide();
                }
            };

            var updateCustomLayout = function (layoutSelect, layoutValue) {
                updateMessageCustomLayout(layoutSelect, layoutValue);
                updateStepsCustomLayout(layoutValue);
            };

            this._on({
                /**
                 * Remove items
                 */
                'click button[id="grid-layout-item-delete"]': function (event) {
                    var element = $(event.target).closest('[id^="contenttype_layout_item_"]');

                    confirm({
                        title: '',
                        content: $.mage.__('Are you sure you want to delete this item ?'),
                        actions: {
                            confirm: function () {
                                element.find('[name^="layout[item]"][name$="[is_delete]"]').val(1);
                                element.addClass('ignore-validate').hide();
                            }
                        },
                        always: function () {
                            widget._updateItemPositions();
                        }
                    });
                },

                /**
                 * Change select layout
                 */
                'change select[id=contenttype_layout]': function (event, data) {
                    var layoutSelect = $(event.target),
                        layoutValue = layoutSelect.val(),
                        layoutOptGroup = layoutSelect.find('[value="' + layoutValue + '"]')
                            .closest('optgroup').attr('label'),
                        widget = this;

                    // Update preview image
                    updatePreviewImage(layoutSelect, layoutValue);

                    // Update template layout
                    updateTemplateLayout(layoutValue, widget);

                    // Update warning and steps if custom layout is selected
                    updateCustomLayout(layoutSelect, layoutValue);
                },
            });

        },

        /**
         * Update item position
         */
        _updateItemPositions: function () {
            this.element.find('#contenttype-layout-grid div.grid-layout-item .grid-layout-item-alt > [name^="layout[item]"][name$="[sort_order]"]').each(function (index) {
                $(this).val(index);
            });
        },

        /**
         * Update item titles
         */
        _updateItemTitles: function () {
            this.element.find('.grid-layout-item-field').each(function (index) {
                var title = $(this).find('.layout_field_custom_field_id :selected').text();

                $(this).find('.grid-layout-item-title .field-title').text(title);
            });

            this.element.find('.grid-layout-item-group').each(function (index) {
                var label = $(this).find('.layout_group_html_name').val();

                $(this).find('.grid-layout-item-title .group-label').text(label);
            });
        },

        /**
         * Append an existing item to the layout
         */
        addItem: function (value) {
            var element = $('#contenttype-layout-grid #col' + value.column),
                itemTemplate = '';

            // Find the parent if it exists
            if (value.parent_id) {
                element = $('[name^="layout[item][group]"][name$="[id]"][value="' + value.parent_id + '"]');
                element = element.closest('.grid-layout-item-content').find('.grid-layout-item-group-area').first();
                value.parent_id = element.attr('rel');
            }

            itemTemplate = this.getItemTemplate(value, value.item);

            // Append the item to the layout
            element.append(itemTemplate);

            // Update layout
            this._initLayoutItems();
            this._initDragAndDropLayout();
            this._updateItemPositions();

            // Retrieve the item
            var item = element.find('.grid-layout-item').last();
            // Set the item collapsed
            item.find('.admin__collapsible-title').click();
            // Set selected values
            item.find('#layout-' + value.item + '-label_option').val(value.label);
            item.find('#layout-field-custom_field_id').val(value.custom_field_id).trigger('change', value);
            item.find('#layout-block-block_id').val(value.block_id);
        },

        /**
         * Retrieve item template
         */
        getItemTemplate: function (event, item) {
            var tmpl = this.item.template + item,
                element = event.target || event.srcElement || event.currentTarget,
                data = {};

            this.item[item].count = parseInt(this.item[item].count, 10) + 1;

            if (typeof element !== 'undefined') {
                element = $(element);
                data.uid = 0;
                data.parent_id = element.attr('rel');
                data.html_label_tag = 'div';
                data.html_tag = 'div';
                if (element.hasClass('column')) {
                    data.column = element.attr('id').replace('col', '');
                } else {
                    data.column = element.parent().find('[name^="layout[item]"][name$="[column]"]').val();
                }
            } else {
                data = event;
            }

            data.id = this.item[item].count;
            data.item = item;

            tmpl = mageTemplate(tmpl, {
                data: data
            });

            return tmpl
        }

    });
});
