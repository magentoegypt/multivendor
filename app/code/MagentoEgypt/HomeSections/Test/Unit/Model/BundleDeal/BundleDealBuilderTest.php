<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Test\Unit\Model\BundleDeal;

use MagentoEgypt\HomeSections\Model\BundleDeal\BundleDealBuilder;
use PHPUnit\Framework\TestCase;

/**
 * The bundle card rules of the website's BundleDeals block, as extracted.
 */
final class BundleDealBuilderTest extends TestCase
{
    /** @var array<int, array{price: float, name: string, thumb: string|null}> */
    private const CHILDREN = [
        10 => ['price' => 100.0, 'name' => 'A', 'thumb' => 't10'],
        11 => ['price' => 80.0, 'name' => 'B', 'thumb' => 't11'],
        12 => ['price' => 0.0, 'name' => 'C', 'thumb' => null],
        13 => ['price' => 50.0, 'name' => 'D', 'thumb' => 't13'],
    ];

    public function testKitTotalIsTheCheapestChoicePerOptionTimesQty(): void
    {
        $kit = [
            ['product_ids' => [10, 11], 'qty' => 1.0, 'selection_count' => 2],
            ['product_ids' => [13], 'qty' => 2.0, 'selection_count' => 1],
        ];

        self::assertSame(180.0, BundleDealBuilder::regularTotal($kit, self::CHILDREN, true));
    }

    public function testKitWithAnUnpricedOptionHasNoComparison(): void
    {
        self::assertSame(0.0, BundleDealBuilder::regularTotal([['product_ids' => [12], 'qty' => 1.0]], self::CHILDREN, true));
    }

    public function testChooserComparesTheCheapestRegularPrice(): void
    {
        self::assertSame(50.0, BundleDealBuilder::regularTotal([['product_ids' => [10, 12, 13], 'qty' => 1.0]], self::CHILDREN, false));
    }

    public function testThumbnails(): void
    {
        self::assertSame([10, 13], BundleDealBuilder::thumbIds([['product_ids' => [10, 11]], ['product_ids' => [13]]]));
        self::assertSame([11, 10], BundleDealBuilder::thumbIds([['product_ids' => [11, 10]]]));
    }

    public function testDescriptionFromTheContents(): void
    {
        self::assertSame('Includes A, B, D.', BundleDealBuilder::describeContents([10, 11, 13, 10], self::CHILDREN));
        self::assertSame('Includes A, B, C and 1 more.', BundleDealBuilder::describeContents([10, 11, 12, 13], self::CHILDREN));
        self::assertNull(BundleDealBuilder::describeContents([99], self::CHILDREN));
    }

    public function testExcerpt(): void
    {
        self::assertSame('Hello world', BundleDealBuilder::excerpt('<p>Hello   <b>world</b></p>'));
        self::assertNull(BundleDealBuilder::excerpt('<p> </p>'));
        self::assertSame(141, mb_strlen((string) BundleDealBuilder::excerpt(str_repeat('ب', 200))));
    }

    public function testCategoryChipsNeedTwoDepartments(): void
    {
        self::assertSame([], BundleDealBuilder::categoryChips([['category_ids' => [5]]], [5 => 'Five']));
        self::assertSame(
            [['id' => 6, 'name' => 'Six', 'count' => 2], ['id' => 5, 'name' => 'Five', 'count' => 1]],
            BundleDealBuilder::categoryChips([['category_ids' => [5, 6]], ['category_ids' => [6]]], [6 => 'Six', 5 => 'Five'])
        );
    }

    public function testStats(): void
    {
        self::assertSame(19, BundleDealBuilder::maxDiscount([['discount_percent' => 5], ['discount_percent' => 19]]));
        self::assertSame(2, BundleDealBuilder::sellerCount([['vendor_id' => 0], ['vendor_id' => 3], ['vendor_id' => 3]]));
    }
}
