<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home;

use MagentoEgypt\HubApp\Model\Source\SectionType;

/**
 * Title and subtitle of a Home section in the store view's language.
 *
 *   - Arabic store views (locale ar_*) read title_ar / subtitle_ar, every
 *     other locale title_en / subtitle_en;
 *   - a title of exactly "-" hides the header (null), whatever the defaults;
 *   - an empty title falls back to the type's default title, translated
 *     through __() — the builder runs inside storefront emulation, so the
 *     module and theme i18n of the store view apply;
 *   - then to what the provider suggests (a category rail: the category
 *     name), then null (no header);
 *   - subtitles have no defaults.
 *
 * The other language's text is never used as a fallback: an English title on
 * the Arabic Home is worse than no title.
 */
class TitleResolver
{
    public const HIDE = '-';

    /**
     * English source strings; i18n/ar_SA.csv carries the Arabic, copied from
     * the theme's ar_SA.csv where the website already translates them.
     */
    public const DEFAULT_TITLES = [
        SectionType::CATEGORY_CHIPS => 'Shop by category',
        SectionType::TODAYS_DEALS => "Today's Deals",
        SectionType::PICKED_FOR_YOU => 'Picked For You',
        SectionType::FEATURED_STORES => 'Featured Stores',
        SectionType::BUNDLE_DEALS => 'Bundle Deals',
        SectionType::BEST_SELLERS => 'Best Selling Items',
        SectionType::POPULAR_PRODUCTS => 'Popular Products',
        SectionType::TOP_BRANDS => 'Top Brands on Hub Market',
        SectionType::TOP_VENDORS => 'Top Vendors This Month',
        SectionType::NEW_STORES => 'New Stores on Hub Market',
    ];

    /**
     * @param array<string, mixed> $row table row
     */
    public function title(array $row, bool $arabic, ?string $providerDefault = null): ?string
    {
        $value = trim((string) ($row[$arabic ? 'title_ar' : 'title_en'] ?? ''));
        if ($value === self::HIDE) {
            return null;
        }
        if ($value !== '') {
            return $value;
        }

        $type = strtoupper((string) ($row['type'] ?? ''));
        if (isset(self::DEFAULT_TITLES[$type])) {
            return (string) __(self::DEFAULT_TITLES[$type]);
        }

        $providerDefault = $providerDefault !== null ? trim($providerDefault) : '';

        return $providerDefault !== '' ? $providerDefault : null;
    }

    /**
     * @param array<string, mixed> $row table row
     */
    public function subtitle(array $row, bool $arabic): ?string
    {
        $value = trim((string) ($row[$arabic ? 'subtitle_ar' : 'subtitle_en'] ?? ''));

        return $value !== '' && $value !== self::HIDE ? $value : null;
    }

    public static function isArabic(string $locale): bool
    {
        return str_starts_with(strtolower(trim($locale)), 'ar');
    }
}
