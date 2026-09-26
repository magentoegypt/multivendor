<?php
declare(strict_types=1);

namespace MagentoEgypt\SetExtend\Plugin\Locale;

use Magento\Framework\Locale\LocaleFormatter;
use MagentoEgypt\SetExtend\Model\LatinDigits;

/**
 * Core's LocaleFormatter is where the storefront's non-price numbers come from:
 * the toolbar result count, pager page numbers and the per-page limiter
 * (formatNumber), and `window.LOCALE` (getLocaleJs), which the minicart badge,
 * checkout item counts and price-utils.js hand to toLocaleString(). For ar_SA
 * all of them came out Arabic-Indic next to Latin prices (QA02 BUG-04).
 *
 * Fixing it here rather than per template is the point: a new template that
 * uses the core formatter gets Latin digits without anyone remembering to.
 */
class LatinDigitsFormatter
{
    public function afterFormatNumber(LocaleFormatter $subject, $result)
    {
        return is_string($result) ? LatinDigits::convert($result) : $result;
    }

    public function afterGetLocaleJs(LocaleFormatter $subject, string $result): string
    {
        return LatinDigits::jsLocale($result);
    }
}
