<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Product;

use Magento\CatalogGraphQl\Model\Resolver\Products\DataProvider\Product as ProductDataProvider;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use MagentoEgypt\HubApp\Model\Product\ProductListLoader;
use MagentoEgypt\HubApp\Model\Product\RankedIds;
use MagentoEgypt\HubApp\Model\Product\StockFilter;
use MagentoEgypt\VendorExtend\Model\StorefrontVisibility;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The storefront gate: marketplace, "select and sell" copies, then stock; remembered for the request.
 */
final class ProductListLoaderTest extends TestCase
{
    public function testTheGateAsksForStockAndRemembersItsAnswers(): void
    {
        $visibility = $this->createMock(StorefrontVisibility::class);
        $visibility->expects(self::once())->method('sellableIds')
            ->willReturnCallback(static fn (array $ids): array => array_values(array_diff($ids, [4])));
        $visibility->expects(self::once())->method('searchableIds')->willReturnArgument(0);
        $stock = $this->createMock(StockFilter::class);
        $stock->expects(self::once())->method('inStock')->with([3, 1, 2], 1)->willReturn([3, 1]);

        $loader = new ProductListLoader(
            $this->createMock(ProductDataProvider::class),
            $this->createMock(SearchCriteriaBuilderFactory::class),
            $visibility,
            $stock,
            $this->createMock(LoggerInterface::class)
        );

        self::assertSame([3, 1], $loader->sellable([3, 4, 1, 2], 1));
        self::assertSame([1], $loader->sellable([1, 2, 4], 1), 'answered from memory: no second query');

        $ranked = new RankedIds($loader);
        self::assertSame(
            [['id' => 3, 'to_date' => null]],
            $ranked->shownRows([['id' => 2, 'to_date' => null], ['id' => 3, 'to_date' => null]], 1)
        );
        self::assertSame([1, 3], $ranked->shownIds([1, 2, 3], 1));
    }
}
