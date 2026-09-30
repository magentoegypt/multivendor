<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Product;

/**
 * hmDeals' filters, sorts and department chips over the day's ranked deals. Pure: unit-tested.
 *
 * The rows are RankedLists::deals(): gated, deepest percentage discount first
 * (DealRanker, bundles by their percent special price), at most
 * RankedLists::MAX_RANKED. Nothing here re-ranks the deals: every sort is
 * stable, so offers that tie keep the ranking's order, and DISCOUNT is the
 * ranking itself. The facts come from DealFacts.
 */
final class DealFilter
{
    public const DISCOUNT = 'DISCOUNT';
    public const PRICE_ASC = 'PRICE_ASC';
    public const PRICE_DESC = 'PRICE_DESC';
    public const ENDING_SOON = 'ENDING_SOON';
    public const NEWEST = 'NEWEST';

    public const SORTS = [self::DISCOUNT, self::PRICE_ASC, self::PRICE_DESC, self::ENDING_SOON, self::NEWEST];

    /** Chips need a choice: fewer departments than this show none (the bundle page's rule). */
    private const MIN_CHIPS = 2;

    private function __construct()
    {
    }

    /**
     * The deals in category $categoryId (or below it) with at least $minPercent off.
     *
     * @param array<int, array<string, mixed>> $rows ranked deals
     * @param array<int, array<string, mixed>> $facts DealFacts products
     * @return array<int, array<string, mixed>> the matching rows, ranking order kept
     */
    public static function filter(array $rows, array $facts, ?int $categoryId, ?int $minPercent): array
    {
        return array_values(array_filter($rows, static function (array $row) use ($facts, $categoryId, $minPercent): bool {
            if ($minPercent !== null && $minPercent > 0 && (float) ($row['percent_off'] ?? 0) < $minPercent) {
                return false;
            }
            if ($categoryId !== null) {
                $under = (array) ($facts[(int) $row['id']]['under'] ?? []);

                return in_array($categoryId, array_map('intval', $under), true);
            }

            return true;
        }));
    }

    /**
     * @param array<int, array<string, mixed>> $rows ranked deals
     * @param array<int, array<string, mixed>> $facts DealFacts products
     * @return array<int, array<string, mixed>>
     */
    public static function sort(array $rows, array $facts, string $sort): array
    {
        $rows = array_values($rows);
        if ($sort === self::DISCOUNT || !in_array($sort, self::SORTS, true)) {
            return $rows;
        }

        //  Rank position as the last key: every sort is stable on the ranking.
        $keyed = [];
        foreach ($rows as $rank => $row) {
            $keyed[] = ['rank' => $rank, 'row' => $row, 'fact' => $facts[(int) $row['id']] ?? []];
        }
        usort($keyed, static function (array $a, array $b) use ($sort): int {
            $order = match ($sort) {
                self::PRICE_ASC => self::compareMissingLast($a['fact']['price'] ?? null, $b['fact']['price'] ?? null, false),
                self::PRICE_DESC => self::compareMissingLast($a['fact']['price'] ?? null, $b['fact']['price'] ?? null, true),
                self::ENDING_SOON => self::compareMissingLast(self::day($a['row']['to_date'] ?? null), self::day($b['row']['to_date'] ?? null), false),
                //  The storefront's "Newest": created_at DESC, entity_id DESC.
                self::NEWEST => [(string) ($b['fact']['created_at'] ?? ''), (int) $b['row']['id']]
                    <=> [(string) ($a['fact']['created_at'] ?? ''), (int) $a['row']['id']],
                default => 0,
            };

            return $order !== 0 ? $order : $a['rank'] <=> $b['rank'];
        });

        return array_map(static fn (array $item): array => $item['row'], $keyed);
    }

    /**
     * Department chips of $rows: the active top-level categories they sit in,
     * catalogue order, with how many; empty when fewer than two.
     *
     * @param array<int, array<string, mixed>> $rows deals matching every filter but the category
     * @param array<int, array<string, mixed>> $facts DealFacts products
     * @param array<int, string> $names active department id => name, catalogue order
     * @return array<int, array{id: int, name: string, count: int}>
     */
    public static function departments(array $rows, array $facts, array $names): array
    {
        $counts = [];
        foreach ($rows as $row) {
            foreach (array_unique(array_map('intval', (array) ($facts[(int) $row['id']]['departments'] ?? []))) as $id) {
                if (isset($names[$id])) {
                    $counts[$id] = ($counts[$id] ?? 0) + 1;
                }
            }
        }
        if (count($counts) < self::MIN_CHIPS) {
            return [];
        }

        $chips = [];
        foreach ($names as $id => $name) {
            if (isset($counts[$id])) {
                $chips[] = ['id' => (int) $id, 'name' => (string) $name, 'count' => $counts[$id]];
            }
        }

        return $chips;
    }

    /**
     * Ascending (or descending) with the missing values last either way.
     */
    private static function compareMissingLast(float|string|null $a, float|string|null $b, bool $descending): int
    {
        if ($a === null || $b === null) {
            return ($a === null) <=> ($b === null);
        }

        return $descending ? $b <=> $a : $a <=> $b;
    }

    /**
     * The calendar day of a special_to_date ("2026-10-01 00:00:00"), or null for none.
     */
    private static function day(mixed $toDate): ?string
    {
        $toDate = trim((string) $toDate);

        return preg_match('/^(\d{4}-\d{2}-\d{2})/', $toDate, $m) ? $m[1] : null;
    }
}
