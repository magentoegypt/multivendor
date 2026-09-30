<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Link;

use MagentoEgypt\HubApp\Model\Link\LinkClassifier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Classification of admin-typed destinations into HmLinkType (before url_rewrite).
 */
final class LinkClassifierTest extends TestCase
{
    private LinkClassifier $classifier;

    protected function setUp(): void
    {
        $this->classifier = new LinkClassifier(
            [
                'https://hub-market.magento2.click/en/',
                'http://hub-market.magento2.click/en/',
                'https://hub-market.magento2.click/',
                'http://hub-market.magento2.click/',
            ],
            'en',
            'shop',
            'shop-by-brand'
        );
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: string|null, 3: string|null}>
     *         target, type, path, code
     */
    public static function targets(): array
    {
        return [
            'category page' => ['clothes.html', LinkClassifier::LOOKUP, 'clothes.html', null],
            'leading slash' => ['/clothes.html', LinkClassifier::LOOKUP, 'clothes.html', null],
            'store code segment' => ['en/clothes.html', LinkClassifier::LOOKUP, 'clothes.html', null],
            'own absolute url' => ['https://hub-market.magento2.click/en/clothes.html', LinkClassifier::LOOKUP, 'clothes.html', null],
            'own url without store code' => ['http://hub-market.magento2.click/all.html', LinkClassifier::LOOKUP, 'all.html', null],
            'seller' => ['shop/loly', 'STORE', 'shop/loly', 'loly'],
            'seller case kept' => ['/shop/MIA/', 'STORE', 'shop/MIA', 'MIA'],
            'seller list' => ['sellerlist', 'STORES', 'sellerlist', null],
            'seller route alone' => ['shop', 'STORES', 'shop', null],
            'brand' => ['shop-by-brand/apple.html', 'BRAND', 'shop-by-brand/apple.html', 'apple'],
            'brands via front name' => ['brand', 'BRANDS', 'brand', null],
            'brands via route' => ['shop-by-brand', 'BRANDS', 'shop-by-brand', null],
            'bundles' => ['bundles', 'BUNDLES', 'bundles', null],
            'deals' => ['deals', 'DEALS', 'deals', null],
            'search' => ['catalogsearch/result/?q=red+shoes', 'SEARCH', 'catalogsearch/result', 'red shoes'],
            'search without text' => ['catalogsearch/result', 'EXTERNAL', 'catalogsearch/result', null],
            'home' => ['/', 'EXTERNAL', '', null],
        ];
    }

    #[DataProvider('targets')]
    public function testClassify(string $target, string $type, ?string $path, ?string $code): void
    {
        $result = $this->classifier->classify($target);

        self::assertNotNull($result);
        self::assertSame($type, $result['type']);
        self::assertSame($path, $result['path']);
        self::assertSame($code, $result['code']);
    }

    public function testEmptyIsNull(): void
    {
        self::assertNull($this->classifier->classify(null));
        self::assertNull($this->classifier->classify('   '));
    }

    public function testOtherHostsAndSchemesAreExternalAndUntouched(): void
    {
        $result = $this->classifier->classify('https://example.com/promo?x=1');
        self::assertSame('EXTERNAL', $result['type']);
        self::assertSame('https://example.com/promo?x=1', $result['external']);

        $mail = $this->classifier->classify('mailto:care@hubmarket.ae');
        self::assertSame('EXTERNAL', $mail['type']);
        self::assertSame('mailto:care@hubmarket.ae', $mail['external']);
    }

    public function testQueryIsKeptAndFragmentDropped(): void
    {
        $result = $this->classifier->classify('all.html?cat=3#top');

        self::assertSame('all.html', $result['path']);
        self::assertSame('cat=3', $result['query']);
    }

    public function testNoSellerOrBrandRouteConfigured(): void
    {
        $bare = new LinkClassifier(['https://example.test/'], 'en', '', '');

        self::assertSame(LinkClassifier::LOOKUP, $bare->classify('shop/loly')['type']);
        self::assertSame('BRANDS', $bare->classify('brand')['type']);
    }
}
