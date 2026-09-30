<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Store;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use MagentoEgypt\HubApp\Api\MediaUrlInterface;
use MagentoEgypt\HubAppVendors\Model\Store\ReviewProducts;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * The reviewed products: a URL key only for products the storefront lists.
 */
final class ReviewProductsTest extends TestCase
{
    public function testOnlyListedProductsCanBeOpened(): void
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects(self::once())->method('setStoreId')->with(2)->willReturnSelf();
        $collection->method('addAttributeToSelect')->willReturnSelf();
        $collection->expects(self::once())->method('addIdFilter')->with([301, 302, 303])->willReturnSelf();
        $collection->method('getItems')->willReturn([
            $this->product(301, 'Corner Sofa Bed', 'corner-sofa-bed'),
            $this->product(302, 'Old Lamp', 'old-lamp'),
            $this->product(303, ' ', 'nameless'),
        ]);
        $factory = $this->createMock(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);
        $media = $this->createMock(MediaUrlInterface::class);
        $media->method('productImage')->willReturnCallback(
            static fn (Product $product, string $imageId, int $storeId): ?string => $product->getId() === 301
                ? 'https://m/cache/' . $imageId . '/301.jpg'
                : null
        );

        $products = (new ReviewProducts($factory, $media, $this->createMock(LoggerInterface::class)))
            ->forPage([301, 302, 303, 301, 0], [301], 2);

        self::assertSame(
            [
                301 => [
                    'name' => 'Corner Sofa Bed',
                    'url_key' => 'corner-sofa-bed',
                    'thumbnail_url' => 'https://m/cache/product_thumbnail_image/301.jpg',
                ],
                //  Reviewed, but no longer listed (disabled, unapproved, hidden): named, not linked.
                302 => ['name' => 'Old Lamp', 'url_key' => null, 'thumbnail_url' => null],
            ],
            $products
        );
    }

    public function testNoProductsNoQuery(): void
    {
        $factory = $this->createMock(CollectionFactory::class);
        $factory->expects(self::never())->method('create');

        self::assertSame(
            [],
            (new ReviewProducts($factory, $this->createMock(MediaUrlInterface::class), $this->createMock(LoggerInterface::class)))
                ->forPage([0], [], 1)
        );
    }

    private function product(int $id, string $name, string $urlKey): Product
    {
        $product = $this->createMock(Product::class);
        $product->method('getId')->willReturn($id);
        $product->method('getName')->willReturn($name);
        $product->method('getData')->willReturnCallback(
            static fn ($key = '') => $key === 'url_key' ? $urlKey : null
        );

        return $product;
    }
}
