<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Home\Provider;

use Magento\Catalog\Model\ResourceModel\Category\Collection as CategoryCollection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\DataObject;
use MagentoEgypt\HubApp\Api\LinkResolverInterface;
use MagentoEgypt\HubApp\Model\Home\Provider\ProductRailProvider;
use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Product\RankedLists;
use MagentoEgypt\HubApp\Model\Source\ProductSort;
use PHPUnit\Framework\TestCase;

/**
 * Category rails name a category by id (the seeded ones this store's); a category that does not exist
 * in the store view leaves its rail out of the Home instead of failing it.
 */
final class ProductRailProviderTest extends TestCase
{
    public function testACategoryRailWhoseCategoryDoesNotExistIsLeftOut(): void
    {
        $lists = $this->createMock(RankedLists::class);
        //  The category-product index has no row for an unknown category.
        $lists->expects(self::once())->method('catalog')->with(1, 101, ProductSort::NEWEST, 4)->willReturn([]);

        self::assertNull($this->provider($lists, null)->provide($this->context(101)));
    }

    public function testACategoryRailWithProductsTakesTheCategoryName(): void
    {
        $lists = $this->createMock(RankedLists::class);
        $lists->method('catalog')->willReturn([11, 12]);

        $result = $this->provider($lists, ['entity_id' => 74, 'name' => 'Furniture', 'request_path' => 'furniture.html'])
            ->provide($this->context(74));

        self::assertNotNull($result);
        self::assertSame([11, 12], $result->getProductIds());
        self::assertSame('Furniture', $result->getDefaultTitle());
    }

    public function testARailWithoutACategoryIsLeftOut(): void
    {
        $lists = $this->createMock(RankedLists::class);
        $lists->expects(self::never())->method('catalog');

        self::assertNull($this->provider($lists, null)->provide($this->context(null)));
    }

    /**
     * @param array<string, mixed>|null $category the active category the store view finds, if any
     */
    private function provider(RankedLists $lists, ?array $category): ProductRailProvider
    {
        $collection = $this->createMock(CategoryCollection::class);
        foreach (['setStoreId', 'addAttributeToSelect', 'addAttributeToFilter', 'addIdFilter', 'addUrlRewriteToResult'] as $method) {
            $collection->method($method)->willReturnSelf();
        }
        $item = new DataObject($category ?? []);
        $item->setId($category['entity_id'] ?? null);
        $collection->method('getFirstItem')->willReturn($item);
        $factory = $this->createMock(CategoryCollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $links = $this->createMock(LinkResolverInterface::class);
        $links->method('category')->willReturn(['type' => 'CATEGORY', 'url' => 'furniture.html']);

        return new ProductRailProvider($lists, $factory, $links);
    }

    private function context(?int $categoryId): SectionContext
    {
        return new SectionContext(
            ['section_id' => 6, 'type' => 'CATEGORY_RAIL', 'category_id' => $categoryId, 'item_limit' => 4],
            1,
            'en',
            1,
            'en_US',
            'GUEST',
            new \DateTimeImmutable('2026-09-30 10:00:00', new \DateTimeZone('UTC')),
            'Asia/Riyadh'
        );
    }
}
