<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Model\Pricing;

/**
 * The figures of a priced package (HmBundleQuote), from its unit price. Pure: unit-tested.
 *
 * Rounded the way the cart rounds a line (Quote\Item\AbstractItem::calcRowTotal):
 * the unit price to the cent first, then the row total of that rounded unit
 * times the quantity, so row_total is exactly what the cart line will show.
 *
 * The saving follows the bundle cards (HomeSections BundleDealBuilder): it is
 * only reported when it is real, more than half a cent; otherwise the regular
 * total is left out too, so the app never strikes through the same price.
 */
final class PackageTotals
{
    /** A difference below this is rounding, not a saving. */
    public const MIN_SAVING = 0.005;

    private function __construct()
    {
    }

    /**
     * @param float $unit one package in the request's currency (the cart line's unit price)
     * @param float $quantity packages, > 0
     * @param float|null $regular one package's items at their regular prices, same currency; null when unknown
     * @return array{price: float, row_total: float, regular_total: float|null, saving: float|null, discount_percent: int}
     */
    public static function of(float $unit, float $quantity, ?float $regular): array
    {
        $price = self::round(max(0.0, $unit));
        $out = [
            'price' => $price,
            'row_total' => self::round($price * $quantity),
            'regular_total' => null,
            'saving' => null,
            'discount_percent' => 0,
        ];
        if ($regular === null) {
            return $out;
        }

        $regular = self::round($regular);
        $saving = self::round($regular - $price);
        if ($regular <= 0 || $saving < self::MIN_SAVING) {
            return $out;
        }
        $out['regular_total'] = $regular;
        $out['saving'] = $saving;
        $out['discount_percent'] = (int) round($saving / $regular * 100);

        return $out;
    }

    /**
     * To the cent, half away from zero: PriceCurrencyInterface::round().
     */
    public static function round(float $amount): float
    {
        return round($amount, 2);
    }
}
