<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Model;

/**
 * Product ids the current request's listing is restricted to, for Algolia.
 *
 * The storefront's non-attribute layered filters (Vendors, Minimum Rating,
 * Availability — MagentoEgypt\VendorExtend\Model\Layer\Filter\AbstractIdFilter)
 * narrow the listing with an SQL `e.entity_id IN (...)`. That is enough for
 * OpenSearch, but with Algolia the total and the paging come from Algolia, so
 * the count stayed at the unfiltered total and later pages lost matches
 * (QA01 2026-09-25: "loly store" showed 7 products under "12 results").
 * Each filter also records its ids here; Plugin\ServerSearchNoAnalytics adds
 * them to the Algolia query as an objectID restriction. Several filters
 * intersect.
 *
 * Static and per request on purpose: PHP-FPM starts every request clean, and a
 * plain holder needs no DI wiring under compiled production DI.
 */
class IdRestriction
{
    /** @var list<int>|null null = no restriction */
    private static ?array $ids = null;

    /** @param int[] $ids */
    public static function restrictTo(array $ids): void
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        self::$ids = self::$ids === null ? $ids : array_values(array_intersect(self::$ids, $ids));
    }

    /** @return list<int>|null */
    public static function ids(): ?array
    {
        return self::$ids;
    }

    /** Algolia `filters` expression for the restriction, or null when there is none. */
    public static function filterExpression(): ?string
    {
        if (self::$ids === null) {
            return null;
        }

        if (!self::$ids) {
            return 'objectID:0'; // restricted to nothing: match nothing
        }

        return '(' . implode(' OR ', array_map(static fn(int $id): string => 'objectID:' . $id, self::$ids)) . ')';
    }
}
