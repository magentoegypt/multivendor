<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Store;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use MagentoEgypt\HubApp\Model\Seller\ListableProducts;
use MagentoEgypt\HubApp\Model\Seller\SellerDirectory;
use MagentoEgypt\HubAppVendors\Model\Resolver\Identity\CategoryCountIdentity;
use MagentoEgypt\HubAppVendors\Model\Resolver\Identity\StoreCategoriesIdentity;
use MagentoEgypt\HubAppVendors\Model\Store\SellerCategories;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Sellers by top-level category: the chips' counts and each card's primary category.
 */
final class SellerCategoriesTest extends TestCase
{
    /** Top-level menu categories of root 2, in menu order. */
    private const CATEGORIES = [34 => 'Furniture', 35 => 'Grocery', 36 => 'Fashion'];

    public function testChipsCountTheSellersEachCategoryHolds(): void
    {
        $categories = $this->categories($this->cacheMiss());

        self::assertSame(
            [
                'total_count' => 3,
                'items' => [
                    ['id' => 34, 'uid' => 'MzQ=', 'name' => 'Furniture', 'count' => 2],
                    ['id' => 35, 'uid' => 'MzU=', 'name' => 'Grocery', 'count' => 2],
                ],
            ],
            $categories->chips(1)
        );
        self::assertSame(
            ['hm_vendor', 'cat_c_34', 'cat_c_35'],
            (new StoreCategoriesIdentity())->getIdentities($categories->chips(1))
        );
    }

    public function testThePrimaryCategoryHoldsMostOfTheSellersProducts(): void
    {
        $categories = $this->categories($this->cacheMiss());

        //  MIA: 301 and 302 in Furniture (302 twice, in two children: once), 303 in Grocery.
        self::assertSame(['id' => 34, 'uid' => 'MzQ=', 'name' => 'Furniture', 'count' => 2], $categories->primary(7, 1));
        //  Loly: one product in each; the tie goes to menu order.
        self::assertSame(['id' => 34, 'uid' => 'MzQ=', 'name' => 'Furniture', 'count' => 1], $categories->primary(8, 1));
        //  Enara's product is filed under no top-level menu category; 99 is not a seller.
        self::assertNull($categories->primary(9, 1));
        self::assertNull($categories->primary(99, 1));

        self::assertSame(['cat_c_34'], (new CategoryCountIdentity())->getIdentities(['id' => 34]));
        self::assertSame([], (new CategoryCountIdentity())->getIdentities([]));
    }

    public function testACachedBuildNeedsNoQuery(): void
    {
        $cache = $this->createMock(AppCache::class);
        $cache->method('load')->with('seller_categories_1')->willReturn([
            'categories' => [['id' => 34, 'name' => 'Furniture']],
            'counts' => ['7' => ['34' => 3], '8' => []],
        ]);
        $cache->expects(self::never())->method('save');

        $categories = $this->categories($cache, false);

        self::assertSame(['total_count' => 2, 'items' => [['id' => 34, 'uid' => 'MzQ=', 'name' => 'Furniture', 'count' => 1]]], $categories->chips(1));
        self::assertSame(3, $categories->primary(7, 1)['count']);
    }

    private function cacheMiss(): AppCache
    {
        $cache = $this->createMock(AppCache::class);
        $cache->method('load')->willReturn(null);
        $cache->expects(self::once())->method('save')->with(
            'seller_categories_1',
            self::isType('array'),
            ['hm_vendor', 'cat_c'],
            AppCache::MAX_TTL
        );

        return $cache;
    }

    private function categories(AppCache $cache, bool $queried = true): SellerCategories
    {
        $items = [];
        foreach (self::CATEGORIES as $id => $name) {
            $category = $this->createMock(Category::class);
            $category->method('getId')->willReturn($id);
            $category->method('getName')->willReturn($name);
            $items[] = $category;
        }
        $collection = $this->createMock(Collection::class);
        foreach (['setStoreId', 'addAttributeToSelect', 'addAttributeToFilter', 'addFieldToFilter', 'addAttributeToSort'] as $method) {
            $collection->method($method)->willReturnSelf();
        }
        $collection->method('getIterator')->willReturn(new \ArrayIterator($items));
        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects($queried ? self::once() : self::never())->method('create')->willReturn($collection);

        $store = $this->createMock(Store::class);
        $store->method('getRootCategoryId')->willReturn(2);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $directory = $this->createMock(SellerDirectory::class);
        $directory->method('approvedIds')->willReturn([7, 8, 9, 10]);
        $listable = $this->createMock(ListableProducts::class);
        $listable->method('forVendors')->willReturn([
            7 => [301, 302, 303],
            8 => [401, 402],
            9 => [501],
            10 => [],       // approved, nothing listable: not a Stores seller
        ]);

        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('join')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->expects($queried ? self::once() : self::never())->method('fetchAll')->willReturn([
            ['product_id' => '301', 'path' => '1/2/34'],
            ['product_id' => '302', 'path' => '1/2/34/340'],
            ['product_id' => '302', 'path' => '1/2/34/341'],
            ['product_id' => '303', 'path' => '1/2/35/350'],
            ['product_id' => '401', 'path' => '1/2/34'],
            ['product_id' => '402', 'path' => '1/2/35'],
            ['product_id' => '501', 'path' => '1/2/77'],   // not a menu category
            ['product_id' => '501', 'path' => '1/2'],      // the root itself
        ]);
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        $uid = $this->createMock(Uid::class);
        $uid->method('encode')->willReturnCallback(static fn (string $id): string => base64_encode($id));

        return new SellerCategories(
            $resource,
            $factory,
            $storeManager,
            $listable,
            $directory,
            $cache,
            $uid,
            $this->createMock(LoggerInterface::class)
        );
    }
}
