<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Store;

use MagentoEgypt\HubAppVendors\Model\Store\StoreSorter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MagentoEgypt\HubAppVendors\Model\Store\StoreSorter
 */
class StoreSorterTest extends TestCase
{
    /**
     * @return array<int, array<string, mixed>>
     */
    private function cards(): array
    {
        return [
            $this->card(1, 'alpha', 'Alpha', 4.5, 10, 12, false, '2025-01-10 08:00:00'),
            $this->card(2, 'beta', 'beta', null, 0, 30, true, '2026-03-01 08:00:00'),
            $this->card(3, 'gamma', 'Gamma', 4.8, 2, 5, false, '2024-06-01 08:00:00'),
            $this->card(4, 'delta', 'Delta', 4.5, 25, 12, true, '2026-09-01 08:00:00'),
            $this->card(5, 'zero', 'Zero', 0.0, 3, 1, false, '2023-01-01 08:00:00'),
        ];
    }

    private function card(
        int $id,
        string $code,
        string $name,
        ?float $rating,
        int $reviews,
        int $products,
        bool $featured,
        string $joined
    ): array {
        return [
            'vendor_entity_id' => $id,
            'code' => $code,
            'name' => $name,
            'rating' => $rating,
            'review_count' => $reviews,
            'product_count' => $products,
            'is_featured' => $featured,
            'joined_at' => (new \DateTimeImmutable($joined, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $cards
     * @return int[]
     */
    private static function ids(array $cards): array
    {
        return array_map(static fn (array $card): int => $card['vendor_entity_id'], $cards);
    }

    public function testFeaturedFirstThenRatingThenName(): void
    {
        //  Featured: delta (4.5) before beta (unrated); then gamma 4.8, alpha 4.5, zero 0.0.
        $this->assertSame([4, 2, 3, 1, 5], self::ids(StoreSorter::sort($this->cards(), StoreSorter::FEATURED)));
    }

    public function testNullSortWithoutCodesIsFeatured(): void
    {
        $this->assertSame(
            self::ids(StoreSorter::sort($this->cards(), StoreSorter::FEATURED)),
            self::ids(StoreSorter::sort($this->cards(), null))
        );
    }

    public function testTopRatedPutsUnratedLastAndBreaksTiesOnReviews(): void
    {
        //  gamma 4.8; delta and alpha tie at 4.5 -> more reviews first; zero 0.0; beta unrated last.
        $this->assertSame([3, 4, 1, 5, 2], self::ids(StoreSorter::sort($this->cards(), StoreSorter::TOP_RATED)));
    }

    public function testNewestFirst(): void
    {
        $this->assertSame([4, 2, 1, 3, 5], self::ids(StoreSorter::sort($this->cards(), StoreSorter::NEWEST)));
    }

    public function testNameIsCaseInsensitive(): void
    {
        $this->assertSame([1, 2, 4, 3, 5], self::ids(StoreSorter::sort($this->cards(), StoreSorter::NAME)));
    }

    public function testNameUsesTheGivenComparator(): void
    {
        $reverse = static fn (string $a, string $b): int => strcasecmp($b, $a);
        $this->assertSame([5, 3, 4, 2, 1], self::ids(StoreSorter::sort($this->cards(), StoreSorter::NAME, [], $reverse)));
    }

    public function testProductCountThenName(): void
    {
        //  beta 30; alpha and delta tie at 12 -> name; gamma 5; zero 1.
        $this->assertSame([2, 1, 4, 3, 5], self::ids(StoreSorter::sort($this->cards(), StoreSorter::PRODUCT_COUNT)));
    }

    public function testCodesOrderWhenNoSortIsGiven(): void
    {
        $this->assertSame(
            [3, 1, 5],
            self::ids(StoreSorter::sort(
                [$this->cards()[0], $this->cards()[2], $this->cards()[4]],
                null,
                ['GAMMA', 'alpha', 'zero']
            ))
        );
    }

    public function testExplicitSortBeatsCodesOrder(): void
    {
        $this->assertSame(
            [4, 1],
            self::ids(StoreSorter::sort([$this->cards()[0], $this->cards()[3]], StoreSorter::NEWEST, ['alpha', 'delta']))
        );
    }

    public function testTiesAreStableOnSellerId(): void
    {
        $twins = [
            $this->card(9, 'b', 'Same', 4.0, 1, 1, false, '2025-01-01 00:00:00'),
            $this->card(7, 'a', 'Same', 4.0, 1, 1, false, '2025-01-01 00:00:00'),
        ];
        $this->assertSame([7, 9], self::ids(StoreSorter::sort($twins, StoreSorter::TOP_RATED)));
        $this->assertSame([9, 7], self::ids(StoreSorter::sort($twins, StoreSorter::NEWEST)));
    }

    public function testNormalise(): void
    {
        $this->assertSame(StoreSorter::TOP_RATED, StoreSorter::normalise(' top_rated '));
        $this->assertNull(StoreSorter::normalise('PRICE_ASC'));
        $this->assertNull(StoreSorter::normalise(null));
        $this->assertNull(StoreSorter::normalise(''));
    }

    public function testEmptyList(): void
    {
        $this->assertSame([], StoreSorter::sort([], StoreSorter::FEATURED));
    }
}
