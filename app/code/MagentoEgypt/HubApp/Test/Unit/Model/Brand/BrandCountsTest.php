<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Brand;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\HubApp\Api\LinkResolverInterface;
use MagentoEgypt\HubApp\Api\MediaUrlInterface;
use MagentoEgypt\HubApp\Api\ProductListLoaderInterface;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubApp\Model\Brand\BrandCounts;
use MagentoEgypt\HubApp\Model\Brand\BrandReader;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \MagentoEgypt\HubApp\Model\Brand\BrandCounts
 * @covers \MagentoEgypt\HubApp\Model\Brand\BrandReader::page
 */
class BrandCountsTest extends TestCase
{
    public function testTheStoreViewsValueWinsOverTheDefaultOne(): void
    {
        $rows = [
            ['entity_id' => '1', 'store_id' => '0', 'value' => '101'],
            ['entity_id' => '1', 'store_id' => '2', 'value' => '102'],   // store view override
            ['entity_id' => '2', 'store_id' => '2', 'value' => '103'],
            ['entity_id' => '2', 'store_id' => '0', 'value' => '101'],   // default after the override: ignored
            ['entity_id' => '3', 'store_id' => '0', 'value' => '101,104'],   // multiselect
            ['entity_id' => '4', 'store_id' => '0', 'value' => ''],
        ];

        self::assertSame([1 => [102], 2 => [103], 3 => [101, 104]], BrandCounts::optionsByProduct($rows, 2));
        self::assertSame([1 => [101], 2 => [101], 3 => [101, 104]], BrandCounts::optionsByProduct($rows, 1));
    }

    public function testOnlyListedProductsCountAndHubMarketIsOneSeller(): void
    {
        $options = [1 => [101], 2 => [101], 3 => [101, 104], 4 => [101], 5 => [105]];
        $listed = [1, 2, 3, 5];                          // 4 fails the storefront gate
        $vendors = [1 => 7, 2 => 7, 3 => 0, 4 => 9, 5 => 0];

        self::assertSame(
            [
                101 => ['products' => 3, 'sellers' => 2],   // seller 7 and Hub Market
                104 => ['products' => 1, 'sellers' => 1],
                105 => ['products' => 1, 'sellers' => 1],
            ],
            BrandCounts::tally($options, $listed, $vendors)
        );
        self::assertSame([], BrandCounts::tally($options, [], $vendors));
    }

    public function testCachedCountsAreReadOncePerRequest(): void
    {
        $cache = $this->createMock(AppCache::class);
        $cache->expects(self::once())->method('load')->with('brand_counts_1')
            ->willReturn(['101' => ['products' => 12, 'sellers' => 2]]);
        $counts = new BrandCounts(
            $this->createMock(ResourceConnection::class),
            $this->createMock(EavConfig::class),
            $this->createMock(ProductListLoaderInterface::class),
            $cache,
            $this->createMock(LoggerInterface::class)
        );

        self::assertSame(['products' => 12, 'sellers' => 2], $counts->forOption(101, 1));
        self::assertSame(['products' => 0, 'sellers' => 0], $counts->forOption(999, 1), 'a brand without products');
        self::assertSame([101], array_keys($counts->forStore(1)));
    }

    public function testWithProductsKeepsTheListedOptionsInAdminOrder(): void
    {
        $cache = $this->createMock(AppCache::class);
        $cache->method('load')->willReturn([
            ['id' => 1, 'option_id' => 101, 'name' => 'Samsung', 'is_featured' => true],
            ['id' => 2, 'option_id' => 102, 'name' => 'Empty', 'is_featured' => true],
            ['id' => 3, 'option_id' => 103, 'name' => 'HP', 'is_featured' => false],
        ]);
        $reader = new BrandReader(
            $this->createMock(ResourceConnection::class),
            $this->createMock(MediaUrlInterface::class),
            $this->createMock(LinkResolverInterface::class),
            $this->createMock(StorefrontEmulationInterface::class),
            $cache,
            $this->createMock(LoggerInterface::class)
        );

        $page = $reader->page(1, false, 10, 1, [103, 101]);
        self::assertSame(['Samsung', 'HP'], array_column($page['items'], 'name'));
        self::assertSame(2, $page['total_count']);

        $page = $reader->page(1, true, 10, 1, [103, 101]);
        self::assertSame(['Samsung'], array_column($page['items'], 'name'), 'featured and with products');

        self::assertSame(3, $reader->page(1, false, 10, 1)['total_count'], 'no filter');
    }
}
