define([
    'jquery',
    'Magento_Ui/js/lib/knockout/bindings/range',
    'Magento_Theme/js/sortable'
], function ($) {
    'use strict';

    $.widget('amasty.checkoutFields', {
        options: {
            sliderMinValue: 0,
            sliderMaxValue: 100,
            sliderStep: 10,
            tabIndexNoFocus: -1,
            sortOrderStep: 10,
            selectors: {
                fieldset: '[data-role="field-set"]',
                sortableRows: 'tr:not(.placeholder):not(.unsortable)',
                sortOrderInput: 'input[data-role="sort-order"]',
                enabledInput: 'input[data-role="enabled"]',
                useDefaultCheckbox: 'input[data-role="use-default"]',
                widthInput: '[data-role="width-input"]',
                widthSlider: '[data-role="width-slider"]',
                widthControl: '[data-role="width-control"]',
                fieldCell: '[data-role="field"]',
                rowLabel: '[data-role="label"]',
                requiredCheckbox: '[data-role="checkbox"]',
                checkboxCell: '.checkbox-cell',
                checkboxInput: 'input[type=checkbox]',
                fieldGroupTable: '[data-role="fields-table"]',
                tableRow: 'tr'
            }
        },

        /**
         * @returns {void}
         */
        _create: function () {
            var self = this;

            this.handleWidthControl();
            this.handleUseDefaultCheckbox();

            $(this.options.selectors.fieldset).sortable({
                connectWith: this.options.selectors.fieldset,
                items: this.options.selectors.sortableRows,
                update: self.updateFieldInfo.bind(self),
                receive: self.onReceive.bind(self)
            });

            $(this.options.selectors.checkboxCell).click(function (event) {
                if (event.target.localName !== 'input') {
                    $(this).children(self.options.selectors.checkboxInput).click();
                }
            });

            $(this.options.selectors.fieldGroupTable + ':first thead td').each(function (columnIndex) {
                var maxWidth = $(this).outerWidth();

                $(self.options.selectors.fieldGroupTable + ' thead td:nth-child(' + (columnIndex + 1) + ')').each(
                    function (i, td) {
                        maxWidth = Math.max($(td).outerWidth(), maxWidth);
                    }
                );

                $(self.options.selectors.fieldGroupTable + ' td:visible:nth-child(' + (columnIndex + 1) + ')')
                    .css({ width: maxWidth });
            });
        },

        /**
         * @returns {void}
         */
        handleWidthControl: function () {
            var self = this;

            $(this.options.selectors.widthControl).each(function (i, element) {
                var $input = $(this).find(self.options.selectors.widthInput),
                    $slider = $(element).children(self.options.selectors.widthSlider),
                    $useDefaultCheckbox = $(this).parents(self.options.selectors.tableRow)
                        .find(self.options.selectors.useDefaultCheckbox);

                $input.on('change', function () {
                    $($slider).slider('value', this.value);
                });

                $slider.on('mousedown', function (e) {
                    if (self.isDefaultValueSelected($useDefaultCheckbox)) {
                        e.stopImmediatePropagation();
                        e.preventDefault();
                    }
                });

                $slider.slider({
                    value: $input.val(),
                    min: self.options.sliderMinValue,
                    max: self.options.sliderMaxValue,
                    step: self.options.sliderStep,
                    slide: function (event, ui) {
                        if (!$useDefaultCheckbox.length) {
                            $input.val(ui.value);

                            return;
                        }

                        if (!$useDefaultCheckbox[0].checked) {
                            $input.val(ui.value);
                        }
                    }
                });

                if (self.isDefaultValueSelected($useDefaultCheckbox)) {
                    $slider.find('a').prop('tabindex', self.options.tabIndexNoFocus);
                }
            });
        },

        /**
         * @returns {void}
         */
        handleUseDefaultCheckbox: function () {
            var self = this;

            $(this.options.selectors.useDefaultCheckbox).change(function () {
                var $row = $(this).parents(self.options.selectors.tableRow),
                    isEnabled = Boolean($row.closest(self.options.selectors.fieldset).data('enabled')),
                    $requiredCheckbox = $row.find(self.options.selectors.requiredCheckbox);

                self.toggleInputFocus($row.find(self.options.selectors.rowLabel), !this.checked);
                self.toggleInputFocus($row.find(self.options.selectors.widthInput), !this.checked);
                self.toggleInputFocus($row.find(self.options.selectors.widthSlider + ' a'), !this.checked);

                if (isEnabled && !$requiredCheckbox.data('is-restricted')) {
                    self.toggleInputFocus($requiredCheckbox, !this.checked);
                }
            });
        },

        /**
         * @param {jQuery} $checkbox
         * @returns {boolean}
         */
        isDefaultValueSelected: function ($checkbox) {
            return $checkbox.length && $checkbox[0].checked;
        },

        /**
         *
         * @param {jQuery} $node
         * @param {boolean} canFocus
         * @returns {void}
         */
        toggleInputFocus: function ($node, canFocus) {
            $node.prop('readonly', !canFocus);

            if (!canFocus) {
                $node.prop('tabindex', this.options.tabIndexNoFocus);

                return;
            }

            $node.removeProp('tabindex');
        },

        /**
         * @returns {void}
         */
        updateFieldInfo: function () {
            var self = this;

            $(this.options.selectors.fieldset).each(function (i, fieldset) {
                self.updateSortOrder(fieldset);
                self.updateStatus(fieldset);
            });
        },

        /**
         * @param {Node} fieldset
         * @returns {void}
         */
        updateSortOrder: function (fieldset) {
            var self = this;

            $(fieldset).find(this.options.selectors.sortOrderInput).each(function (i, input) {
                input.value = (i + 1) * self.options.sortOrderStep;
            });
        },

        /**
         * @param {Node} fieldset
         * @returns {void}
         */
        updateStatus: function (fieldset) {
            var status = $(fieldset).data('enabled');

            $(fieldset).find(this.options.selectors.enabledInput).each(function (i, input) {
                input.value = status;
            });
        },

        /**
         * @param {jQuery.Event} event
         * @param {object} ui
         * @returns {void}
         */
        onReceive: function (event, ui) {
            var $requiredCheckbox = ui.item.find(this.options.selectors.requiredCheckbox),
                isEnabled = Boolean($(event.target).data('enabled'));

            this.toggleInputFocus($requiredCheckbox, isEnabled);

            if (!isEnabled && !$requiredCheckbox.data('is-restricted')) {
                $requiredCheckbox.prop('checked', false);
            }
        }
    });

    return $.amasty.checkoutFields;
});
