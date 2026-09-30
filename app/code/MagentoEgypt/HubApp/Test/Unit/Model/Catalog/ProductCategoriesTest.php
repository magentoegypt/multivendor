<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Catalog;

use MagentoEgypt\HubApp\Model\Catalog\ProductCategories;
use MagentoEgypt\HubApp\Model\Product\DealFacts;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MagentoEgypt\HubApp\Model\Catalog\ProductCategories
 * @covers \MagentoEgypt\HubApp\Model\Product\DealFacts
 */
class ProductCategoriesTest extends TestCase
{
    public function testAProductIsInEveryCategoryAboveItsAssignmentsUnderTheStoreRoot(): void
    {
        $placed = ProductCategories::place([
            ['product_id' => '7', 'path' => '1/2/10/15'],
            ['product_id' => '7', 'path' => '1/2/10'],
            ['product_id' => '7', 'path' => '1/2/20/21/22'],
            ['product_id' => '8', 'path' => '1/2'],             // the root itself: no department
            ['product_id' => '8', 'path' => '1/99/40'],         // another store's tree
            ['product_id' => '9', 'path' => '1/2/30'],
        ], 2);

        self::assertSame([7 => [10, 15, 20, 21, 22], 9 => [30]], $placed['under']);
        self::assertSame([7 => [10, 20], 9 => [30]], $placed['departments']);
    }

    public function testCachedFactsGetTheirIntKeysBack(): void
    {
        $json = json_decode((string) json_encode([
            'products' => [
                7 => ['under' => ['10', 15], 'departments' => [10], 'created_at' => '2026-09-01 10:00:00', 'price' => '25.5'],
                9 => ['under' => [], 'departments' => [], 'created_at' => '', 'price' => null],
            ],
            'names' => [20 => 'Furniture', 10 => 'Grocery'],
        ]), true);

        $restored = DealFacts::restore($json);

        self::assertSame(
            [
                7 => ['under' => [10, 15], 'departments' => [10], 'created_at' => '2026-09-01 10:00:00', 'price' => 25.5],
                9 => ['under' => [], 'departments' => [], 'created_at' => '', 'price' => null],
            ],
            $restored['products']
        );
        self::assertSame([20 => 'Furniture', 10 => 'Grocery'], $restored['names'], 'catalogue order kept');
        self::assertNull(DealFacts::restore(null));
        self::assertNull(DealFacts::restore(['names' => []]));
    }
}
