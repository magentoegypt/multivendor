<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Media;

use Magento\Catalog\Helper\ImageFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubApp\Model\Media\MediaUrl;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Absolute HTTPS media URLs from stored paths.
 *
 * ImageFactory is a generated class: run with Magento's unit test bootstrap
 * (dev/tests/unit/phpunit.xml.dist), which generates it on demand.
 */
final class MediaUrlTest extends TestCase
{
    private MediaUrl $mediaUrl;

    protected function setUp(): void
    {
        $bases = [
            UrlInterface::URL_TYPE_MEDIA . ':1' => 'https://hub-market.test/media/',
            UrlInterface::URL_TYPE_MEDIA . ':0' => 'http://hub-market.test/media/',
            UrlInterface::URL_TYPE_WEB . ':1' => 'https://hub-market.test/',
            UrlInterface::URL_TYPE_WEB . ':0' => 'http://hub-market.test/',
            UrlInterface::URL_TYPE_LINK . ':1' => 'https://hub-market.test/en/',
            UrlInterface::URL_TYPE_LINK . ':0' => 'http://hub-market.test/en/',
        ];
        $store = $this->createMock(Store::class);
        $store->method('getBaseUrl')->willReturnCallback(
            static fn (string $type, ?bool $secure = null): string => $bases[$type . ':' . (int) $secure] ?? ''
        );
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $this->mediaUrl = new MediaUrl(
            $storeManager,
            $this->createMock(ImageFactory::class),
            $this->createMock(StorefrontEmulationInterface::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testEmptyIsNull(): void
    {
        self::assertNull($this->mediaUrl->media(null, 1));
        self::assertNull($this->mediaUrl->media('  ', 1));
        self::assertNull($this->mediaUrl->media('no_selection', 1));
    }

    public function testRelativePathGetsTheSecureMediaBase(): void
    {
        self::assertSame('https://hub-market.test/media/hero/hero-grocery.jpg', $this->mediaUrl->media('hero/hero-grocery.jpg', 1));
        self::assertSame('https://hub-market.test/media/mgs_brand/a.png', $this->mediaUrl->media('/mgs_brand/a.png', 1));
    }

    public function testMediaPrefixInTheValueIsNotDoubled(): void
    {
        self::assertSame('https://hub-market.test/media/catalog/category/x.jpg', $this->mediaUrl->media('/media/catalog/category/x.jpg', 1));
        self::assertSame('https://hub-market.test/media/wysiwyg/y.jpg', $this->mediaUrl->media('pub/media/wysiwyg/y.jpg', 1));
    }

    public function testOwnHttpUrlsAreUpgradedOthersAreNot(): void
    {
        self::assertSame('https://hub-market.test/media/a.jpg', $this->mediaUrl->media('http://hub-market.test/media/a.jpg', 1));
        self::assertSame('https://hub-market.test/en/clothes.html', $this->mediaUrl->secure('http://hub-market.test/en/clothes.html', 1));
        self::assertSame('http://cdn.example.com/a.jpg', $this->mediaUrl->media('http://cdn.example.com/a.jpg', 1));
        self::assertSame('https://cdn.example.com/a.jpg', $this->mediaUrl->media('//cdn.example.com/a.jpg', 1));
    }

    public function testSellerFolders(): void
    {
        self::assertSame('https://hub-market.test/media/ves_vendors/logo/l.png', $this->mediaUrl->sellerLogo('l.png', 1));
        self::assertSame('https://hub-market.test/media/ves_vendors/logo/l.png', $this->mediaUrl->sellerLogo('ves_vendors/logo/l.png', 1));
        self::assertSame('https://hub-market.test/media/ves_vendors/banner/b.jpg', $this->mediaUrl->sellerBanner('/b.jpg', 1));
        self::assertNull($this->mediaUrl->sellerBanner('', 1));
    }
}
