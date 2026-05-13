
/*jshint browser:true jquery:true*/
/*global alert*/

define([
        "jquery", 'mage/template', "Magento_Ui/js/modal/alert",
        "prototype", "extjs/ext-tree", "extjs/ext-tree-checkbox", "jquery/ui"
    ], function ($, mageTemplate, alert) {

    "use strict";

    $.widget('vnecoms.appInstance', {

        options: {
           // pageGroupTemplate : '',
            addPageGroupBtn: '.btn-add-layout-button',
            pageGroupContainerId : '[data-role="page_group_container"]',
            removePageGroupBtn: '.btn-remove-layout-button',
            activePageGroups : $H({}),
            selectedItems : {}
        },

        _create: function () {
            this.count = 0;
            this.pageGroupContainerId = this.options.pageGroupContainerId;
            this.pageGroupTemplate = this.options.pageGroupTemplate;
            this.activePageGroups = this.options.activePageGroups;
            this.selectedItems = this.options.selectedItems;

            console.log('create');
            this._bind();
            this._super();
        },

        /**
         * Event binding
         * @private
         */
        _bind: function () {
            var btnAddPage = $(this.options.addPageGroupBtn);
          //  var btnRemovePage = $(this.options.removePageGroupBtn);

           // console.log(btnRemovePage);

            btnAddPage.on('click', this._addPageGroupClick.bind(this));
           // btnRemovePage.delegate(btnRemovePage,'click', this._removePageGroupClick.bind(this));
        },

        /**
         * @param event
         * @private
         */
        _addPageGroupClick: function (event) {
            this._addPageGroup({});
        },

        _removePageGroupClick: function (event) {
            console.log('click remove');
            this._removePageGroup($(event.target));
        },

        removePageGroup: function (event) {
            this._removePageGroup(event);
        },

        /**
         * Add Page Group Container
         * @param data
         * @private
         */
        _addPageGroup: function (data) {
            console.log('add page');
           // console.log(this.pageGroupTemplate);
            if (this.pageGroupTemplate && ($(this.pageGroupContainerId))) {
                var pageGroupContainer = $(this.pageGroupContainerId)[0];
                if (!data.option_id) {
                    data = {};
                    data.option_id = 0;
                    data.entities = '';
                }
                data.id = this.count++;
                if (data[data.group + '_entities']) {
                    var selected_entities = data[data.group + '_entities'].split(',');
                    if (selected_entities.length > 0) {
                        for (var i = 0; i < selected_entities.length; i++) {
                            this._addProductItemToSelection(data.group + '_ids_' + data.id, selected_entities[i]);
                        }
                    }
                }
                var pageGroupTemplateObj = mageTemplate(this.pageGroupTemplate);
               // console.log($(this.pageGroupContainerId));
                Element.insert(pageGroupContainer, {
                    'bottom': pageGroupTemplateObj({
                        data: data
                    })
                });
                //console.log(data);
                if (data.group) {
                    var pageGroup = $(data.group+'_'+data.id);
                    var additional = {};
                    additional.selectedLayoutHandle = data.layout_handle;
                    additional.selectedBlock = data.block;
                    additional.selectedTemplate = data.template;
                    additional.position = data.position;
                    additional.for_value = data.for_value;
                    additional.template = '';
                    if (data.group == 'pages' || data.group == 'page_layouts') {
                        var layoutSelect = pageGroup.down('select#layout_handle');
                        if (layoutSelect) {
                            for (var i = 0; i < layoutSelect.options.length; i++) {
                                if (layoutSelect.options[i].value == data.layout_handle) {
                                    layoutSelect.options[i].selected = true;
                                    break;
                                }
                            }
                        }
                    }
                    if ($(this.pageGroupContainerId+'_'+data.id)) {
                        var selectGroupElm = $(this.pageGroupContainerId+'_'+data.id).down('select.page_group_select');
                        for (var i = 0; i < selectGroupElm.options.length; i++) {
                            if (selectGroupElm.options[i].value == data.group) {
                                selectGroupElm.options[i].selected = true;
                                break;
                            }
                        }
                    }
                    var forElm = pageGroup.down('input.for_'+data.for_value);
                    if (forElm) {
                        /**
                         * IE browsers fix: remove default checked attribute in radio form element
                         * to check others radio form elements in future
                         */
                        pageGroup.down('input.for_all').defaultChecked = false;
                        forElm.defaultChecked = true;
                        forElm.checked = true;
                        this._togglePageGroupChooser(forElm);
                    }
                    this._displayPageGroup(pageGroup, additional);
                }
            }
        },

        /**
         *
         * @param element
         * @private
         */
        _removePageGroup: function (element) {
            console.log('remove');
            var container = element.up('div.page_group_container');
            if (container) {
                container.remove();
            }
        },

        _addProductItemToSelection: function (groupId, item) {
            if (undefined == this.selectedItems[groupId]) {
                this.selectedItems[groupId] = $H({});
            }
            if (!isNaN(parseInt(item))) {
                this.selectedItems[groupId].set(item, 1);
            }
        },
        
        _removeProductItemFromSelection: function (groupId, item) {
            if (undefined == this.selectedItems[groupId]) {
                this.selectedItems[groupId] = $H({});
            }
            if (!isNaN(parseInt(item))) {
                this.selectedItems[groupId].set(item, 1);
            }
        },
        
        _showBlockContainer: function (container) {
            container = $(container);
            if (container) {
                container.removeClassName('no-display');
                container.removeClassName('ignore-validate');
                container.up('.fieldset-wrapper').addClassName('opened');
                container.select('input', 'select').each(function (element) {
                    $(element).removeAttribute('disabled');
                });
                container.show();
            }
        },
        
        _hideBlockContainer: function (container) {
            container = $(container);
            if (container) {
                container.addClassName('no-display');
                container.addClassName('ignore-validate');
                container.select('input', 'select').each(function (element) {
                    $(element).writeAttribute('disabled', 'disabled');
                });
                container.hide();
            }
        },
        
        _displayPageGroup: function (container, additional) {
            container = $(container);
            if (!container) {
//            if (activePageGroupId = this.activePageGroups.get(container.up('div.page_group_container').id)) {
//                this.hideBlockContainer(activePageGroupId);
//            }
            }
            if (!additional) {
                additional = {};
            }
            if (activePageGroupId = this.activePageGroups.get(container.up('div.page_group_container').id)) {
                this.hideBlockContainer(activePageGroupId);
            }
            this.activePageGroups.set(container.up('div.page_group_container').id, container.id);
            this.showBlockContainer(container);
            blockContainer = container.down('div.block_reference');
            if (blockContainer && blockContainer.innerHTML == '') {
                layoutHandle = '';
                if (layoutHandleField = container.down('input.layout_handle_pattern')) {
                    layoutHandle = layoutHandleField.value;
                } else if (additional.selectedLayoutHandle) {
                    layoutHandle = additional.selectedLayoutHandle;
                }
                this._loadSelectBoxByType('block_reference', container, layoutHandle, additional);
            }
            // this.loadSelectBoxByType('block_template', container, additional.selectedBlock, additional);
        },
        
        _displayEntityChooser: function () {
            
        },
        
        _hideEntityChooser: function () {
            
        },
        
        _displayChooser: function () {
            
        },
        
        _checkProduct: function () {
            
        },
        
        _togglePageGroupChooser: function () {
            
        },

        /**
         * load select box by type
         * @param type
         * @param element
         * @param value
         * @param additional
         * @private
         */
        _loadSelectBoxByType: function (type, element, value, additional) {
            if (!additional) {
                additional = {};
            }
            if (element && (containerElm = element.down('div.'+type))) {
            }
        }

    });

    return $.vnecoms.appInstance;
});
