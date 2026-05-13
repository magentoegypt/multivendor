define([
  'jquery',
], function ($) {
    'use strict';

    return function (SwatchRenderer) {
        $.widget('mage.SwatchRenderer', $.mage.SwatchRenderer, {
            _init: function () {
                this._super();
            },
            /**
             * Event for swatch options
             *
             * @param {Object} $this
             * @param {Object} $widget
             * @private
             */
            _OnClick: function ($this, $widget) {
              this._super($this, $widget);
              if (this.getProduct()) {
                var comparison = requirejs('uiRegistry').get("component = Vnecoms_VendorsPriceComparison/js/vendor");
                if (comparison != undefined) {
                  comparison.unselectCountry();
                  var attributes = this._getAttributeSelected($widget);
                  if (attributes) {
                    comparison.setAttributes(attributes);
                  }
                }
              }
            },
            /**
             * Event for select
             *
             * @param {Object} $this
             * @param {Object} $widget
             * @private
             */
            _OnChange: function ($this, $widget) {
              this._super($this, $widget);
              if (this.getProduct()) {
                var comparison = requirejs('uiRegistry').get("component = Vnecoms_VendorsPriceComparison/js/vendor");
                if (comparison != undefined) {
                  comparison.unselectCountry();
                  var attributes = this._getAttributeSelected($widget);
                  if (attributes) {
                    comparison.setAttributes(attributes);
                  }
                }
              }
            },

            /**
             * [description]
             * @param  {[type]} $widget [description]
             * @return {[type]}         [description]
             */
            _getAttributeSelected: function( $widget ) {
              var attributes = [];
              var isFull = true;
              $widget.element.find('.' + $widget.options.classes.attributeClass).each(function () {
                  var id = $(this).attr('attribute-id'),
                      code = $(this).attr('attribute-code'),
                      option = $(this).attr('option-selected');
                  if (!option || option === undefined) {
                    isFull = false;
                  }
                  if (!$widget.optionsMap.hasOwnProperty(id) || !$widget.optionsMap[id].hasOwnProperty(option)) {
                      isFull = false;
                      return;
                  }
                  attributes.push({'attribute': code, 'value': option, 'id': id});
              });
              if (!isFull) return false;
              return attributes;
            }

        });
        return $.mage.SwatchRenderer;
    };
});
