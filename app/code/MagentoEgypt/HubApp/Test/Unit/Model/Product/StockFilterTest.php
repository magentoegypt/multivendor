<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Product;

use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Model\ResourceModel\Stock\Status as StockStatusResource;
use MagentoEgypt\HubApp\Model\Product\StockFilter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Out-of-stock products leave the lists only when the store view hides them, through core's own stock filter.
 */
final class StockFilterTest extends TestCase
{
    public function testShowingOutOfStockProductsFiltersNothing(): void
    {
        $factory = $this->createMock(ProductCollectionFactory::class);
        $factory->expects(self::never())->method('create');

        self::assertSame([3, 1, 2], $this->filter(true, $factory)->inStock([3, 1, 2], 1));
    }

    public function testHidingThemAsksCoreStockFilterAndKeepsTheOrder(): void
    {
        $collection = $this->createMock(ProductCollection::class);
        $collection->expects(self::once())->method('addIdFilter')->with([3, 1, 2])->willReturnSelf();
        $collection->method('setStoreId')->willReturnSelf();
        $collection->method('getAllIds')->willReturn(['1', '3']);
        $factory = $this->createMock(ProductCollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $status = $this->createMock(StockStatusResource::class);
        $status->expects(self::once())->method('addStockDataToCollection')->with($collection, true);

        self::assertSame([3, 1], $this->filter(false, $factory, $status)->inStock([3, 1, 2], 1));
    }

    public function testAFailingStockQueryPassesEverything(): void
    {
        $factory = $this->createMock(ProductCollectionFactory::class);
        $factory->method('create')->willThrowException(new \RuntimeException('no index'));

        self::assertSame([3, 1], $this->filter(false, $factory)->inStock([3, 1], 1));
    }

    private function filter(
        bool $showOutOfStock,
        ProductCollectionFactory $factory,
        ?StockStatusResource $status = null
    ): StockFilter {
        $config = $this->createMock(StockConfigurationInterface::class);
        $config->method('isShowOutOfStock')->willReturn($showOutOfStock);

        return new StockFilter(
            $config,
            $status ?? $this->createMock(StockStatusResource::class),
            $factory,
            $this->createMock(LoggerInterface::class)
        );
    }
}
