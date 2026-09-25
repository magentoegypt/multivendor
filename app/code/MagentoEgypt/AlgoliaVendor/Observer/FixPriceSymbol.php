<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Algolia's formatted prices look exactly like the storefront's.
 *
 * The storefront formats prices through MagentoEgypt\SetExtend's
 * NumberFormatter preference (frontend area only): no decimals, Latin digits
 * in Arabic too ("1,000 ج.م."), "EGP 1,000" in English. Algolia builds its
 * `*_formated` strings from indexers that run outside the frontend area, with
 * Magento's own formatting, so the autocomplete showed "١٬٠٠٠٫٠٠ ج.م." and
 * "EGP 1,000.00" next to category pages showing "1,000 ج.م." and "EGP 1,000"
 * (QA01 2026-09-25, BUG-06). Earlier the English records even carried the
 * Arabic symbol ("ج.م.‏500.00"), which is where this observer's name comes from.
 *
 * Each number in every `*_formated` value — a single price, a "min - max"
 * range, a "was" price — is read back (Latin or Arabic-Indic digits) and
 * formatted again with the storefront formatter for the record's store locale.
 * Numeric fields (`default`, `default_max`) are untouched: sorting, filters and
 * the price band still use the exact amounts.
 *
 * No constructor dependencies on purpose (compiled DI, see AddPriceRange).
 */
class FixPriceSymbol implements ObserverInterface
{
    private const STOREFRONT_FORMATTER = \MagentoEgypt\SetExtend\Plugin\Price\NumberFormatter::class;

    /** Arabic-Indic digits and separators => Latin/English equivalents. */
    private const NORMALISE = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '٬' => ',', '٫' => '.',
    ];

    /** @var array<string, object> locale => storefront formatter */
    private static array $formatters = [];

    public function execute(Observer $observer): void
    {
        $transport = $observer->getData('custom_data');
        $product = $observer->getData('productObject');

        if (!$transport instanceof \Magento\Framework\DataObject || $product === null) {
            return;
        }

        $prices = $transport->getData('price');
        $formatter = $this->formatterFor((int) $product->getStoreId());

        if (!is_array($prices) || $formatter === null) {
            return;
        }

        foreach ($prices as $currency => $values) {
            if (!is_array($values)) {
                continue;
            }

            foreach ($values as $key => $value) {
                if (is_string($value) && str_ends_with((string) $key, '_formated')) {
                    $prices[$currency][$key] = $this->reformat($value, (string) $currency, $formatter);
                }
            }
        }

        $transport->setData('price', $prices);
    }

    private function reformat(string $formatted, string $currency, object $formatter): string
    {
        $normalised = strtr($formatted, self::NORMALISE);

        if (!preg_match_all('/\d[\d,]*(?:\.\d+)?/', $normalised, $matches)) {
            return $formatted;
        }

        $parts = [];

        foreach ($matches[0] as $token) {
            $text = $formatter->formatCurrency((float) str_replace(',', '', $token), $currency);
            if ($text === false) {
                return $formatted;
            }
            $parts[] = $text;
        }

        return implode(' - ', $parts);
    }

    private function formatterFor(int $storeId): ?object
    {
        $locale = (string) ObjectManager::getInstance()->get(ScopeConfigInterface::class)
            ->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $storeId);

        if ($locale === '' || !class_exists(self::STOREFRONT_FORMATTER)) {
            return null;
        }

        if (!isset(self::$formatters[$locale])) {
            $class = self::STOREFRONT_FORMATTER;
            self::$formatters[$locale] = new $class($locale, \Magento\Framework\NumberFormatter::CURRENCY);
        }

        return self::$formatters[$locale];
    }
}
