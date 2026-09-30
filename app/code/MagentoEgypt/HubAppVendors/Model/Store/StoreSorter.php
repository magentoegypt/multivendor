<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

/**
 * Orders seller cards (HmStoreSort). Pure: no Magento, unit-tested.
 *
 * Sellers are few (a few dozen), so lists are sorted in PHP after filtering.
 * Every order ends in a stable tie-break (name, then seller id) so a page never
 * shows a seller twice or skips one between requests.
 *
 *   FEATURED       featured first, then rating (unrated last), then name
 *   TOP_RATED      rating, then review count; unrated last (the website's Top Vendors)
 *   NEWEST         join date, newest first (the website's New Stores)
 *   NAME           localised name A-Z
 *   PRODUCT_COUNT  most listable products first
 *
 * No sort with `codes` keeps the order of the codes (admin-chosen Featured Stores).
 *
 * A card is an array with at least: vendor_entity_id, code, name, rating
 * (float|null), review_count, product_count, is_featured, joined_at (ISO-8601 UTC).
 */
final class StoreSorter
{
    public const FEATURED = 'FEATURED';
    public const TOP_RATED = 'TOP_RATED';
    public const NEWEST = 'NEWEST';
    public const NAME = 'NAME';
    public const PRODUCT_COUNT = 'PRODUCT_COUNT';

    public const ALL = [self::FEATURED, self::TOP_RATED, self::NEWEST, self::NAME, self::PRODUCT_COUNT];

    public const DEFAULT_SORT = self::FEATURED;

    private function __construct()
    {
    }

    /**
     * The HmStoreSort value for an input, or null when it is not one.
     */
    public static function normalise(?string $sort): ?string
    {
        $sort = strtoupper(trim((string) $sort));

        return in_array($sort, self::ALL, true) ? $sort : null;
    }

    /**
     * @param array<int|string, array<string, mixed>> $cards
     * @param string|null $sort HmStoreSort value; null = order of $codes when given, else FEATURED
     * @param string[] $codes seller codes in the wanted order (case-insensitive)
     * @param callable(string, string): int|null $compareNames locale-aware name comparison; natural,
     *        case-insensitive order when null
     * @return array<int, array<string, mixed>> the cards, re-indexed
     */
    public static function sort(array $cards, ?string $sort, array $codes = [], ?callable $compareNames = null): array
    {
        $cards = array_values($cards);
        $sort = self::normalise($sort);
        $compareNames ??= static fn (string $a, string $b): int => strnatcasecmp($a, $b);

        if ($sort === null && $codes) {
            $position = [];
            foreach (array_values($codes) as $index => $code) {
                $position[mb_strtolower(trim((string) $code), 'UTF-8')] ??= $index;
            }
            $last = count($position);
            usort($cards, static function (array $a, array $b) use ($position, $last): int {
                return [$position[self::codeKey($a)] ?? $last, self::id($a)]
                    <=> [$position[self::codeKey($b)] ?? $last, self::id($b)];
            });

            return $cards;
        }

        $sort ??= self::DEFAULT_SORT;
        $byName = static function (array $a, array $b) use ($compareNames): int {
            $result = $compareNames((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));

            return $result !== 0 ? $result : self::id($a) <=> self::id($b);
        };

        usort($cards, match ($sort) {
            self::FEATURED => static function (array $a, array $b) use ($byName): int {
                return self::flag($b) <=> self::flag($a)
                    ?: self::ratingKey($b) <=> self::ratingKey($a)
                    ?: $byName($a, $b);
            },
            self::TOP_RATED => static function (array $a, array $b) use ($byName): int {
                return self::ratingKey($b) <=> self::ratingKey($a)
                    ?: self::int($b, 'review_count') <=> self::int($a, 'review_count')
                    ?: $byName($a, $b);
            },
            self::NEWEST => static function (array $a, array $b): int {
                return self::time($b) <=> self::time($a)
                    ?: self::id($b) <=> self::id($a);
            },
            self::NAME => $byName,
            self::PRODUCT_COUNT => static function (array $a, array $b) use ($byName): int {
                return self::int($b, 'product_count') <=> self::int($a, 'product_count')
                    ?: $byName($a, $b);
            },
        });

        return $cards;
    }

    /**
     * Rating as a sort key: unrated sorts below every rated seller (a rating of 0.0 included).
     */
    private static function ratingKey(array $card): float
    {
        $rating = $card['rating'] ?? null;

        return $rating === null ? -1.0 : (float) $rating;
    }

    private static function flag(array $card): int
    {
        return !empty($card['is_featured']) ? 1 : 0;
    }

    private static function int(array $card, string $key): int
    {
        return (int) ($card[$key] ?? 0);
    }

    private static function id(array $card): int
    {
        return (int) ($card['vendor_entity_id'] ?? 0);
    }

    private static function time(array $card): int
    {
        $time = strtotime((string) ($card['joined_at'] ?? ''));

        return $time !== false ? $time : 0;
    }

    private static function codeKey(array $card): string
    {
        return mb_strtolower(trim((string) ($card['code'] ?? '')), 'UTF-8');
    }
}
