<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HeroBanner\Test\Unit\Model;

use MagentoEgypt\HeroBanner\Model\BandReader;
use PHPUnit\Framework\TestCase;

/**
 * A store view's banner replaces the all-stores banner at the same position.
 */
final class BandReaderTest extends TestCase
{
    public function testStoreRowReplacesTheAllStoresRowAtItsPosition(): void
    {
        $rows = BandReader::preferStoreScope([
            ['banner_id' => 1, 'store_id' => 0, 'sort_order' => 1],
            ['banner_id' => 2, 'store_id' => 0, 'sort_order' => 2],
            ['banner_id' => 3, 'store_id' => 2, 'sort_order' => 1],
        ], 2);

        self::assertSame([2, 3], array_column($rows, 'banner_id'));
    }

    public function testOtherStoresRowsAreKeptWhenTheStoreHasNone(): void
    {
        $rows = BandReader::preferStoreScope([
            ['banner_id' => 1, 'store_id' => 0, 'sort_order' => 1],
            ['banner_id' => 2, 'store_id' => 0, 'sort_order' => 2],
        ], 1);

        self::assertSame([1, 2], array_column($rows, 'banner_id'));
    }
}
