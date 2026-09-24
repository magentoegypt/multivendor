<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Adds a `price_range` band to every product record (DEV05 Personalization).
 *
 * Personalization learns affinities from FACET VALUES, and `price` is a number:
 * every distinct price would be its own "preference". A band ("250-500") is what
 * lets a strategy learn "this shopper buys in the 250-500 EGP range". The band
 * is an identifier for the strategy and for filters, not display copy, so it is
 * the same string in both languages.
 *
 * Edges follow the catalogue as indexed on 2026-09-24 (EGP, 317 products per
 * store): median 50, p75 260, p90 1100, max 120000.
 *
 * No constructor dependencies on purpose: this install runs compiled DI in
 * production, and an observer with no constructor arguments is instantiated
 * without needing a di:compile (same pattern as AddStorefrontTranslations).
 */
class AddPriceRange implements ObserverInterface
{
    /** Upper bound (exclusive) => label. */
    private const BANDS = [
        50    => '0-50',
        100   => '50-100',
        250   => '100-250',
        500   => '250-500',
        1000  => '500-1000',
        2500  => '1000-2500',
        5000  => '2500-5000',
    ];

    private const TOP_BAND = '5000+';

    public function execute(Observer $observer): void
    {
        $transport = $observer->getData('custom_data');

        if (!$transport instanceof \Magento\Framework\DataObject) {
            return;
        }

        $price = $this->findPrice($transport->getData('price'));

        if ($price === null) {
            return;
        }

        $transport->setData('price_range', self::band($price));
    }

    public static function band(float $price): string
    {
        foreach (self::BANDS as $upper => $label) {
            if ($price < $upper) {
                return $label;
            }
        }

        return self::TOP_BAND;
    }

    /**
     * The record's price block is keyed by currency then customer group
     * (`price.EGP.default`); take the default group of the first currency.
     */
    private function findPrice(mixed $prices): ?float
    {
        if (!is_array($prices)) {
            return null;
        }

        foreach ($prices as $byGroup) {
            if (is_array($byGroup) && isset($byGroup['default']) && is_numeric($byGroup['default'])) {
                return (float) $byGroup['default'];
            }
        }

        return null;
    }
}
