<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

/**
 * Typical dispatch time of a seller (HmDispatchTime), the website's rules. Pure: unit-tested.
 *
 * DECLARED wins: the seller's own `dispatch_time` choice (HomeSections
 * Model\Vendor\Source\DispatchTime values). Otherwise MEASURED: the mean
 * order-to-shipment time over the seller's real shipments, quoted only when at
 * least MIN_SHIPMENTS back it and it is within MAX_DAYS (HomeSections VendorMeta:
 * "ships in 69 days" from demo orders would mislead worse than nothing).
 *
 * Labels are the SHORT ones of the website's store cards (VendorMeta, CL036-QA01
 * item 12: the long sentences broke the card), translated by the caller under
 * storefront emulation — the theme CSVs hold the Arabic.
 */
final class DispatchTime
{
    public const SOURCE_DECLARED = 'DECLARED';
    public const SOURCE_MEASURED = 'MEASURED';

    public const MIN_SHIPMENTS = 2;
    public const MAX_DAYS = 7;

    public const MEASURED_PREFIX = 'measured_';

    /** Declared value => short label source text. */
    public const DECLARED_LABELS = [
        'same_day' => 'Same day',
        'next_day' => 'Next business day',
        'days_2_3' => '2-3 days',
        'days_3_5' => '3-5 days',
        'days_5_7' => '5-7 days',
    ];

    /** Label of a measured time of up to one day. */
    public const MEASURED_ONE_DAY = '24h';

    /** Label of a measured time of N days (%1). */
    public const MEASURED_DAYS = '~%1 days';

    private function __construct()
    {
    }

    /**
     * The declared code for a stored attribute value, or null when none / unknown.
     */
    public static function declaredCode(?string $value): ?string
    {
        $value = trim((string) $value);

        return isset(self::DECLARED_LABELS[$value]) ? $value : null;
    }

    /**
     * Whole days a measured average stands for (1 = within 24 hours), or null when not credible.
     */
    public static function measuredDays(int $shipments, float $averageHours): ?int
    {
        if ($shipments < self::MIN_SHIPMENTS || $averageHours <= 0 || $averageHours > self::MAX_DAYS * 24) {
            return null;
        }

        return $averageHours <= 24 ? 1 : (int) ceil($averageHours / 24);
    }

    public static function measuredCode(int $days): string
    {
        return self::MEASURED_PREFIX . $days;
    }
}
