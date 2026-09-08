/**
 * Hub Market — keep the header search button alive.
 *
 * Mixin for Magento_Search/js/form-mini (the `quickSearch` widget).
 *
 * THE BUG THIS FIXES  (QA CL041-TC01, ClickUp 86d45hdvn)
 * -----------------------------------------------------
 * `form-mini.js` disables the submit button in `_create()`:
 *
 *     this.submitBtn.disabled = true;
 *
 * and only re-enables it from `_onPropertyChange`, which is bound through
 * `_.debounce(..., suggestionDelay)` — 300ms AFTER the user stops typing.
 *
 * Magento/blank paints `[disabled]` with `pointer-events: none`, so while the
 * button is disabled it is not merely inert, it is CLICK-TRANSPARENT: the
 * hit-test at the button's own centre returns `div.actions`, its parent. The
 * button never receives mousedown, so no click and no submit event is ever
 * produced.
 *
 * The theme then overrode the disabled state's OPACITY only (.85 instead of
 * blank's .5), so it rendered as a full-strength orange "Search" button that
 * silently swallowed every click. Measured, before this fix:
 *
 *     on load          disabled=true   pointer-events=none  hit target=div.actions
 *     after 3+ chars   disabled=false  pointer-events=auto  hit target=the button
 *
 * QA clicked Search before typing — precondition 2 of the test case is
 * literally "or the user directly clicks the Search button" — and got nothing.
 * Pressing Enter always worked, which is why it read as button-specific.
 *
 * THE FIX
 * -------
 * The button is never disabled. There is no reason to gate it: `_onSubmit`
 * already blocks a genuinely empty query, and Magento enforces
 * `catalog/search/min_query_length` server-side (a 1-2 character search lands
 * on the results page with "Minimum Search query length is 3"). Gating the
 * button bought nothing and cost us every click made before the debounce.
 *
 * We also give the empty-field click a VISIBLE answer. Core `_onSubmit` calls
 * preventDefault() on an empty query and stops there — correct, but silent,
 * which is indistinguishable from the bug we just fixed. Focus the field and
 * show the hint instead.
 */
define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';

    return function (quickSearch) {
        $.widget('mage.quickSearch', quickSearch, {

            /**
             * Undo core's initial `submitBtn.disabled = true`.
             *
             * @private
             */
            _create: function () {
                this._super();
                this._hmSetSubmitEnabled();
            },

            /**
             * Core re-disables the button here on every keystroke whenever the
             * term is shorter than minSearchLength. Let it run (it owns the
             * suggestion dropdown) and then re-enable.
             *
             * @private
             */
            _onPropertyChange: function () {
                this._super();
                this._hmSetSubmitEnabled();
            },

            /**
             * Core preventDefaults an empty query without telling anyone.
             * Say something.
             *
             * @param {Event} e
             * @private
             */
            _onSubmit: function (e) {
                this._super(e);

                if (e.isDefaultPrevented()) {
                    this._hmShowHint($t('Type something to search for.'));
                    this.element.trigger('focus');
                } else {
                    this._hmHideHint();
                }
            },

            /**
             * The button must never carry `disabled`: blank styles that state
             * with `pointer-events: none`, which is what made it unclickable.
             *
             * @private
             */
            _hmSetSubmitEnabled: function () {
                if (this.submitBtn) {
                    this.submitBtn.disabled = false;
                }
            },

            /**
             * @param {String} message
             * @private
             */
            _hmShowHint: function (message) {
                var hint = this.searchForm.find('.hm-search__hint');

                if (!hint.length) {
                    return;
                }

                hint.text(message).prop('hidden', false);
                clearTimeout(this._hmHintTimer);
                this._hmHintTimer = setTimeout(this._hmHideHint.bind(this), 4000);
            },

            /**
             * @private
             */
            _hmHideHint: function () {
                clearTimeout(this._hmHintTimer);
                this.searchForm.find('.hm-search__hint').prop('hidden', true).text('');
            }
        });

        return $.mage.quickSearch;
    };
});
