<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Product;

use MagentoEgypt\HubApp\Model\Product\DealFilter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MagentoEgypt\HubApp\Model\Product\DealFilter
 */
class DealFilterTest extends TestCase
{
    /** Grocery (10), with Rice (15) below it; Furniture (20); Fashion (30, inactive: no name). */
    private const NAMES = [10 => 'Grocery', 20 => 'Furniture'];

    /**
     * The day's ranking: deepest percentage first (DealRanker), a bundle by its percent special price.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function ranking(): array
    {
        return [
            ['id' => 5, 'type_id' => 'simple', 'percent_off' => 45.0, 'to_date' => '2026-10-03 00:00:00'],
            ['id' => 4, 'type_id' => 'new_bundle', 'percent_off' => 30.0, 'to_date' => null],
            ['id' => 3, 'type_id' => 'simple', 'percent_off' => 30.0, 'to_date' => '2026-10-01 00:00:00'],
            ['id' => 2, 'type_id' => 'simple', 'percent_off' => 12.5, 'to_date' => '2026-10-01 00:00:00'],
            ['id' => 1, 'type_id' => 'simple', 'percent_off' => 5.0, 'to_date' => null],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function facts(): array
    {
        return [
            5 => ['under' => [10, 15], 'departments' => [10], 'created_at' => '2026-09-01 10:00:00', 'price' => 25.0],
            4 => ['under' => [20], 'departments' => [20], 'created_at' => '2026-09-20 10:00:00', 'price' => 263.0],
            3 => ['under' => [10], 'departments' => [10], 'created_at' => '2026-09-20 10:00:00', 'price' => 25.0],
            2 => ['under' => [30, 10, 15], 'departments' => [30, 10], 'created_at' => '2026-08-01 10:00:00', 'price' => null],
            1 => ['under' => [30], 'departments' => [30], 'created_at' => '2026-09-25 10:00:00', 'price' => 9.5],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return int[]
     */
    private static function ids(array $rows): array
    {
        return array_map(static fn (array $row): int => (int) $row['id'], $rows);
    }

    public function testNoFilterKeepsTheRanking(): void
    {
        self::assertSame([5, 4, 3, 2, 1], self::ids(DealFilter::filter(self::ranking(), self::facts(), null, null)));
        self::assertSame([5, 4, 3, 2, 1], self::ids(DealFilter::filter(self::ranking(), self::facts(), null, 0)));
    }

    public function testMinimumDiscountCountsBundlesByTheirPercent(): void
    {
        self::assertSame([5, 4, 3], self::ids(DealFilter::filter(self::ranking(), self::facts(), null, 30)));
        self::assertSame([5, 4, 3, 2], self::ids(DealFilter::filter(self::ranking(), self::facts(), null, 12)));
        self::assertSame([], self::ids(DealFilter::filter(self::ranking(), self::facts(), null, 100)));
    }

    public function testACategoryHoldsWhatIsAssignedAnywhereBelowIt(): void
    {
        self::assertSame([5, 3, 2], self::ids(DealFilter::filter(self::ranking(), self::facts(), 10, null)), 'department');
        self::assertSame([5, 2], self::ids(DealFilter::filter(self::ranking(), self::facts(), 15, null)), 'subcategory');
        self::assertSame([], self::ids(DealFilter::filter(self::ranking(), [], 10, null)), 'unknown placement');
    }

    public function testSortsAreStableOnTheRanking(): void
    {
        $rows = self::ranking();
        $facts = self::facts();

        self::assertSame([5, 4, 3, 2, 1], self::ids(DealFilter::sort($rows, $facts, DealFilter::DISCOUNT)));
        self::assertSame([5, 4, 3, 2, 1], self::ids(DealFilter::sort($rows, $facts, 'SOMETHING_ELSE')));
        //  25 = 25 keeps 5 before 3 (the ranking); no indexed price goes last.
        self::assertSame([1, 5, 3, 4, 2], self::ids(DealFilter::sort($rows, $facts, DealFilter::PRICE_ASC)));
        self::assertSame([4, 5, 3, 1, 2], self::ids(DealFilter::sort($rows, $facts, DealFilter::PRICE_DESC)));
        //  Same end day keeps the ranking (3 before 2); offers without an end last.
        self::assertSame([3, 2, 5, 4, 1], self::ids(DealFilter::sort($rows, $facts, DealFilter::ENDING_SOON)));
        //  created_at DESC, then id DESC, as the storefront's "Newest".
        self::assertSame([1, 4, 3, 5, 2], self::ids(DealFilter::sort($rows, $facts, DealFilter::NEWEST)));
    }

    public function testDepartmentsAreActiveCatalogueOrderWithCounts(): void
    {
        self::assertSame(
            [
                ['id' => 10, 'name' => 'Grocery', 'count' => 3],
                ['id' => 20, 'name' => 'Furniture', 'count' => 1],
            ],
            DealFilter::departments(self::ranking(), self::facts(), self::NAMES)
        );
    }

    public function testOneDepartmentIsNoChoice(): void
    {
        $grocery = DealFilter::filter(self::ranking(), self::facts(), 10, null);
        self::assertSame([], DealFilter::departments($grocery, self::facts(), self::NAMES));
        self::assertSame([], DealFilter::departments([], [], self::NAMES));
    }
}
