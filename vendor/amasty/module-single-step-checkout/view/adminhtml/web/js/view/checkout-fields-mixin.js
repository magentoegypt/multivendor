define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {
        $.widget('amasty.checkoutFields', widget, {
            options: {
                selectors: {
                    placeholder: '[data-role="placeholder"]',
                }
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
                    self.toggleInputFocus($row.find(self.options.selectors.placeholder), !this.checked);
                    self.toggleInputFocus($row.find(self.options.selectors.widthSlider + ' a'), !this.checked);

                    if (isEnabled && !$requiredCheckbox.data('is-restricted')) {
                        self.toggleInputFocus($requiredCheckbox, !this.checked);
                    }
                });
            }
        });

        return $.amasty.checkoutFields;
    };
});
