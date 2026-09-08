/**
 * Hub Market — configurable options the way the prototype draws them
 * (QA cycle 2, item 3-B).
 *
 * The prototype renders every configurable attribute as a labelled row:
 *   COLOR                                   Select Color
 *   [ Select Color…                      ⌄ ]
 *   [Matte Black] [Midnight Blue] [Platinum Silver] [Rose Gold]
 * with an "Please select all options before adding to cart" line and a greyed,
 * inert Add to Cart until every attribute has a value.
 *
 * Magento_Swatches keeps rendering the chips (they carry the price/stock/
 * image logic); this module adds the <select> above each swatch group and
 * keeps the two in step, fills the hint text at the right of the label, and
 * gates the button. Plain <select class="super-attribute-select"> attributes
 * (non-swatch) only take part in the gating.
 */
define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    return function (config, element) {
        var $wrap = $(element),
            $button = $('#product-addtocart-button'),
            $hint = $('.hm-options-hint');

        function optionId($el) {
            return String($el.data('option-id') || $el.attr('data-option-id') || '');
        }

        function labelOf($attr) {
            var label = $attr.find('.swatch-attribute-label').first().text().trim();

            return label.charAt(0).toUpperCase() + label.slice(1);
        }

        // Non-swatch attributes are drawn by the same renderer as a plain
        // <select class="swatch-select">; they take part in the gate and get
        // the label-row hint, but no second select.
        function nativeSelect($attr) {
            return $attr.find('select.swatch-select').first();
        }

        // Magento's placeholder option ("Choose an Option...") carries no value
        // attribute, so jQuery's val() returns its text; go by the index.
        function nativeChosen($native) {
            return $native.length && $native[0].selectedIndex > 0 && $native[0].options[$native[0].selectedIndex].value !== '';
        }

        function build($attr) {
            var $options = $attr.find('.swatch-option'),
                label = labelOf($attr),
                $select;

            if ($attr.data('hm-select')) {
                return;
            }
            if (nativeSelect($attr).length) {
                $attr.find('.swatch-attribute-label').first().after('<span class="hm-option__hint"></span>');
                $attr.data('hm-select', true);
                return;
            }
            if (!$options.length) {
                return;
            }

            $select = $('<select class="hm-option__select"></select>')
                .attr('aria-label', $t('Select %1').replace('%1', label))
                .append($('<option value=""></option>').text($t('Select %1…').replace('%1', label)));

            $options.each(function () {
                var $o = $(this);

                $select.append(
                    $('<option></option>')
                        .val(optionId($o))
                        .text($o.data('option-label') || $o.attr('aria-label') || $o.text().trim())
                        .prop('disabled', $o.hasClass('disabled'))
                );
            });

            $attr.find('.swatch-attribute-options').first().before($select);
            $attr.find('.swatch-attribute-label').first().after('<span class="hm-option__hint"></span>');
            $attr.data('hm-select', true);

            $select.on('change', function () {
                var id = $(this).val(),
                    $selected = $options.filter('.selected'),
                    $target = $options.filter(function () { return optionId($(this)) === id; });

                if (!id) {
                    if ($selected.length) {
                        $selected.trigger('click');   // the renderer toggles it off
                    }
                } else if ($target.length && !$target.hasClass('selected')) {
                    $target.trigger('click');
                }
                setTimeout(sync, 0);
            });
        }

        function sync() {
            $wrap.find('.swatch-attribute').each(function () {
                var $attr = $(this),
                    $select = $attr.find('.hm-option__select'),
                    $selected = $attr.find('.swatch-option.selected'),
                    $hintText = $attr.find('.hm-option__hint');

                if (!$select.length) {
                    var $native = nativeSelect($attr);

                    if ($native.length) {
                        $hintText.text(nativeChosen($native)
                            ? $native.find('option:selected').text().trim()
                            : $t('Select %1').replace('%1', labelOf($attr)));
                    }
                    return;
                }
                $select.val($selected.length ? optionId($selected) : '');
                $select.find('option').each(function () {
                    var $o = $(this), $sw;

                    if (!$o.val()) {
                        return;
                    }
                    $sw = $attr.find('.swatch-option').filter(function () { return optionId($(this)) === $o.val(); });
                    $o.prop('disabled', $sw.hasClass('disabled'));
                });
                $hintText.text($selected.length
                    ? ($selected.data('option-label') || $selected.attr('aria-label') || $selected.text().trim())
                    : $t('Select %1').replace('%1', labelOf($attr)));
            });
            gate();
        }

        function gate() {
            var complete = true;

            $wrap.find('.swatch-attribute').each(function () {
                var $native = nativeSelect($(this));

                if ($native.length ? !nativeChosen($native) : !$(this).find('.swatch-option.selected').length) {
                    complete = false;
                }
            });
            $wrap.find('select.super-attribute-select').each(function () {
                if (!$(this).val()) {
                    complete = false;
                }
            });
            $button.prop('disabled', !complete).toggleClass('hm-is-gated', !complete);
            $hint.toggle(!complete);
        }

        function init() {
            $wrap.find('.swatch-attribute').each(function () { build($(this)); });
            sync();
        }

        // Gate straight away — nothing is selected yet — so the button never
        // shows enabled while the swatch renderer is still drawing.
        gate();

        // The swatch renderer draws its chips after page load; poll briefly.
        (function wait(tries) {
            if ($wrap.find('.swatch-option, select.swatch-select').length || tries > 60) {
                init();
                return;
            }
            setTimeout(function () { wait(tries + 1); }, 150);
        })(0);

        // Magento_Catalog/js/catalog-add-to-cart enables the button when it
        // initialises (after this module ran); re-apply the gate whenever the
        // disabled attribute is flipped by someone else while options are open.
        if (window.MutationObserver && $button.length) {
            new MutationObserver(function () {
                if (!$button.prop('disabled') && $button.hasClass('hm-is-gated')) {
                    $button.prop('disabled', true);
                }
            }).observe($button[0], { attributes: true, attributeFilter: ['disabled'] });
        }

        $wrap.on('click', '.swatch-option', function () { setTimeout(sync, 0); });
        $wrap.on('change', 'select.super-attribute-select, select.swatch-select', function () { setTimeout(sync, 0); });
    };
});
