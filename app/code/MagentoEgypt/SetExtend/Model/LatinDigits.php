<?php
declare(strict_types=1);

namespace MagentoEgypt\SetExtend\Model;

/**
 * The storefront writes every number in Latin digits, Arabic store included.
 *
 * Prices went Latin long ago (Plugin\Price\NumberFormatter formats ar_* as
 * de_DE), and ratings, review counts and Algolia prices followed. Counts that
 * went through ICU's ar_SA formatter still came out Arabic-Indic, so one page
 * mixed both systems — and "٥" (five) reads as a Latin "0": QA02 BUG-04
 * reported 5 products under a "0 results" counter.
 *
 * Anything that formats a number for the storefront should pass it through
 * here (or use a locale with `@numbers=latn`) rather than trusting the locale.
 */
class LatinDigits
{
    private const MAP = [
        // Arabic-Indic
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        // Extended Arabic-Indic (Persian/Urdu forms)
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        // Arabic decimal and thousands separators
        '٫' => '.', '٬' => ',',
    ];

    public static function convert(string $text): string
    {
        return strtr($text, self::MAP);
    }

    /**
     * BCP 47 tag for JS Intl/toLocaleString: `ar-SA` → `ar-SA-u-nu-latn`.
     * Tags that already name a numbering system, and non-Arabic ones, pass through.
     */
    public static function jsLocale(string $locale): string
    {
        if (strncmp($locale, 'ar', 2) !== 0 || strpos($locale, '-u-') !== false) {
            return $locale;
        }

        return $locale . '-u-nu-latn';
    }
}
