<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home;

/**
 * Which section rows are live for a store view, audience and moment.
 *
 * Pure (no Magento), so the rules are unit-tested:
 *   - active;
 *   - store_id 0 (every store view) or the store view itself;
 *   - starts_at empty or not after now; ends_at empty or after now (UTC, the
 *     column is UTC);
 *   - audience "all", or the audience the app asked for (GUEST / CUSTOMER).
 *
 * nextBoundary() tells the cache when the answer changes next.
 */
final class Schedule
{
    public const AUDIENCE_GUEST = 'GUEST';
    public const AUDIENCE_CUSTOMER = 'CUSTOMER';

    private function __construct()
    {
    }

    /**
     * @param array<string, mixed> $row table row
     */
    public static function isVisible(array $row, int $storeId, string $audience, \DateTimeImmutable $nowUtc): bool
    {
        if (!(int) ($row['is_active'] ?? 0)) {
            return false;
        }
        $rowStore = (int) ($row['store_id'] ?? 0);
        if ($rowStore !== 0 && $rowStore !== $storeId) {
            return false;
        }
        if (!self::audienceMatches((string) ($row['audience'] ?? 'all'), $audience)) {
            return false;
        }
        $starts = self::parseUtc($row['starts_at'] ?? null);
        if ($starts !== null && $starts > $nowUtc) {
            return false;
        }
        $ends = self::parseUtc($row['ends_at'] ?? null);

        return $ends === null || $ends > $nowUtc;
    }

    /**
     * @param string $rowAudience all / guest / customer (table)
     * @param string $audience GUEST / CUSTOMER (GraphQL), anything else is GUEST
     */
    public static function audienceMatches(string $rowAudience, string $audience): bool
    {
        $rowAudience = strtolower(trim($rowAudience));
        if ($rowAudience === '' || $rowAudience === Section::AUDIENCE_ALL) {
            return true;
        }

        return $rowAudience === strtolower(self::normaliseAudience($audience));
    }

    public static function normaliseAudience(?string $audience): string
    {
        return strtoupper(trim((string) $audience)) === self::AUDIENCE_CUSTOMER
            ? self::AUDIENCE_CUSTOMER
            : self::AUDIENCE_GUEST;
    }

    /**
     * The first start or end strictly after now among $rows, or null.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public static function nextBoundary(array $rows, \DateTimeImmutable $nowUtc): ?\DateTimeImmutable
    {
        $next = null;
        foreach ($rows as $row) {
            foreach (['starts_at', 'ends_at'] as $column) {
                $moment = self::parseUtc($row[$column] ?? null);
                if ($moment !== null && $moment > $nowUtc && ($next === null || $moment < $next)) {
                    $next = $moment;
                }
            }
        }

        return $next;
    }

    /**
     * A UTC datetime column value, or null for empty / zero / unparseable.
     */
    public static function parseUtc(mixed $value): ?\DateTimeImmutable
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }
        try {
            return new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * ISO-8601 UTC ("2026-10-01T21:00:00Z") of a column value, or null.
     */
    public static function isoUtc(mixed $value): ?string
    {
        $date = self::parseUtc($value);

        return $date?->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
