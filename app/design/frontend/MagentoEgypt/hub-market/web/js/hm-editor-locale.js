/**
 * Arabic UI strings for the storefront WYSIWYG, and the RTL switch for it.
 *
 * The editor on the returns screens is HugeRTE 1.0.4 (Magento 2.4.8 replaced
 * TinyMCE with it; `tinymce` is an alias of the `hugerte` global, set by
 * Magento_Ui's requirejs shim). Magento ships NO language packs for it -
 * lib/web/hugerte has no langs/ directory - so `language: 'ar'` on its own
 * would fetch langs/ar.js, 404, and silently fall back to English.
 *
 * HugeRTE skips that fetch entirely when the code is already registered:
 *
 *     if (!I18n.hasCode(languageCode) && languageCode !== 'en') { ...load... }
 *
 * so registering here and depending on this module before `tinymce.init` runs
 * means no request is made and nothing can 404.
 *
 * WHY THESE KEYS AND NOT A FULL PACK: the set below is not guesswork and not a
 * copy of TinyMCE's LGPL pack. It is every string the editor actually renders
 * with our toolbar, enumerated from a live instance - all `[title]`,
 * `[aria-label]` and swatch labels under `.tox` - with the toolbar Vnecoms
 * configures:
 *
 *     undo redo | formatselect | bold italic forecolor
 *              | alignleft aligncenter alignright alignjustify | removeformat
 *
 * Two findings from that enumeration are worth keeping:
 *
 *   - `formatselect` renders NOTHING. It is the TinyMCE 4 name; the control is
 *     called `blocks` from TinyMCE 5 on. That is why the client's screenshot
 *     shows no format dropdown between Redo and Bold. Left as-is: adding the
 *     dropdown back is a feature, not a translation. Its labels are therefore
 *     deliberately absent from this map.
 *   - The colour button's tooltip is `Text color {0}` with the colour NAME
 *     substituted, so the 24 swatch names have to be here too or the tooltip
 *     reads "لون النص Black".
 *
 * `_dir: 'rtl'` is what makes HugeRTE mirror its own chrome - I18n.isRtl()
 * tests exactly that key. `directionality` (set by apply() below) is a separate
 * thing: it governs the direction of the CONTENT the shopper types.
 */
define(['tinymce'], function (tinymce) {
    'use strict';

    tinymce.addI18n('ar', {
        _dir: 'rtl',

        //  Toolbar buttons, in the order they render.
        'Undo': 'تراجع',
        'Redo': 'إعادة',
        'Bold': 'عريض',
        'Italic': 'مائل',
        'Text color': 'لون النص',
        'Text color {0}': 'لون النص {0}',
        'Background color': 'لون الخلفية',
        'Background color {0}': 'لون الخلفية {0}',
        'Align left': 'محاذاة إلى اليسار',
        'Align center': 'توسيط',
        'Align right': 'محاذاة إلى اليمين',
        'Justify': 'ضبط',
        'Clear formatting': 'إزالة التنسيق',

        //  The editing area itself, announced to screen readers, plus the
        //  resize handle's instructions. The handle lives in the status bar,
        //  which the theme hides on these pages (86d4azdjx item 2) - kept so a
        //  page that shows the bar is not half-translated.
        'Rich Text Area': 'منطقة نص منسق',
        'Press the Up and Down arrow keys to resize the editor.':
            'اضغط على مفتاحي السهم لأعلى ولأسفل لتغيير حجم المحرر.',

        //  The colour swatch panel: the two actions, then the 24 default
        //  swatches. These double as the {0} in the button tooltip above.
        'Remove color': 'إزالة اللون',
        'Custom color': 'لون مخصص',
        'Custom...': 'مخصص...',
        'Black': 'أسود',
        'White': 'أبيض',
        'Red': 'أحمر',
        'Dark Red': 'أحمر غامق',
        'Light Red': 'أحمر فاتح',
        'Green': 'أخضر',
        'Light Green': 'أخضر فاتح',
        'Yellow': 'أصفر',
        'Light Yellow': 'أصفر فاتح',
        'Purple': 'بنفسجي',
        'Dark Purple': 'بنفسجي غامق',
        'Light Purple': 'بنفسجي فاتح',
        'Blue': 'أزرق',
        'Dark Blue': 'أزرق غامق',
        'Light Blue': 'أزرق فاتح',
        'Navy Blue': 'كحلي',
        'Dark Turquoise': 'فيروزي غامق',
        'Orange': 'برتقالي',
        'Gray': 'رمادي',
        'Light Gray': 'رمادي فاتح',
        'Medium Gray': 'رمادي متوسط',
        'Dark Gray': 'رمادي غامق',

        //  Reachable from "Custom color". Not enumerated from the live panel
        //  (the dialog would not open headlessly), so these are the only
        //  entries here taken from the control's documented labels rather than
        //  observed output. R/G/B/Hex are deliberately left untranslated -
        //  they are read as symbols in Arabic colour pickers too.
        'Color Picker': 'منتقي الألوان',
        'Color': 'اللون',
        'Save': 'حفظ',
        'Cancel': 'إلغاء'
    });

    return {
        /**
         * True on the Arabic store.
         *
         * Keyed off <html lang>, the same signal the theme's CSS uses - see
         * the `html:lang(ar)` rules; `[dir='rtl']` never matches on this site
         * because no dir attribute is rendered. Reading it here rather than
         * passing the locale in from PHP keeps these templates free of any
         * locale plumbing, and cannot drift from what the stylesheet does.
         *
         * @returns {Boolean}
         */
        isArabic: function () {
            var lang = (document.documentElement.getAttribute('lang') || '').toLowerCase();

            return lang === 'ar' || lang.indexOf('ar-') === 0 || lang.indexOf('ar_') === 0;
        },

        /**
         * Add the Arabic language and RTL content direction to a tinymce.init
         * config, on the Arabic store only. Returns the same object so it can
         * wrap the config in place.
         *
         * @param {Object} config - a tinymce.init configuration
         * @returns {Object}
         */
        apply: function (config) {
            if (this.isArabic()) {
                config.language = 'ar';
                config.directionality = 'rtl';
            }

            return config;
        }
    };
});
