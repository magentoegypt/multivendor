<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Offer;

use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use MagentoEgypt\HubApp\Api\SellerSummaryProviderInterface;
use MagentoEgypt\HubAppVendors\Model\Offer\FamilyReader;
use MagentoEgypt\HubAppVendors\Model\Offer\OfferFinder;
use MagentoEgypt\HubAppVendors\Model\Offer\OfferGate;
use MagentoEgypt\HubAppVendors\Model\Offer\OfferProducts;
use MagentoEgypt\HubAppVendors\Model\Store\DispatchTimeReader;
use PHPUnit\Framework\TestCase;

/**
 * Other sellers' offers as the website's price comparison lists them. The data is
 * the live Joust Duffle Bag family of 30 Sep 2026: main product 1 sold by test_1
 * (AED 28.90 after a special price), copies 2150 by Hassan Store (AED 40) and
 * 2287 by ENARA (AED 34).
 */
final class OfferFinderTest extends TestCase
{
    private const STORE = 1;

    /** product id => [vendor id, type] */
    private const CATALOGUE = [
        1 => [1, 'simple'],
        2150 => [14, 'simple'],
        2287 => [4, 'simple'],
        2244 => [2, 'simple'],
        2146 => [2, 'simple'],
    ];

    /** product id => [final, regular] */
    private const PRICES = [
        1 => [28.9, 34.0],
        2150 => [40.0, 40.0],
        2287 => [34.0, 34.0],
        2146 => [1000.0, 1000.0],
    ];

    public function testTheMainProductListsItsCopiesCheapestFirst(): void
    {
        $offers = $this->finder()->offers([1], self::STORE, $this->context())[1];

        self::assertSame(['2287', '2150'], array_column($offers, 'sku'));
        self::assertSame(
            ['uid', 'sku', 'url_key', 'type_id', 'seller', 'price', 'regular_price', 'stock_status', 'dispatch_time'],
            array_keys($offers[0]),
            'exactly the HmProductOffer fields'
        );
        self::assertSame('MjI4Nw==', $offers[0]['uid']);
        self::assertSame('key-2287', $offers[0]['url_key']);
        self::assertSame(['value' => 34.0, 'currency' => 'AED'], $offers[0]['price']);
        self::assertSame('ENARA', $offers[0]['seller']['code']);
        self::assertSame('IN_STOCK', $offers[0]['stock_status']);
        self::assertSame(['code' => 'days_2_3', 'label' => '2-3 days', 'source' => 'DECLARED'], $offers[0]['dispatch_time']);
        self::assertNull($offers[1]['dispatch_time'], 'a seller with no dispatch time');
    }

    public function testACopyListsTheMainProductAndTheOtherCopiesButNotItself(): void
    {
        $offers = $this->finder()->offers([2150], self::STORE, $this->context())[2150];

        self::assertSame(['24-MB01', '2287'], array_column($offers, 'sku'));
        self::assertSame(['value' => 34.0, 'currency' => 'AED'], $offers[0]['regular_price']);
        self::assertSame(['value' => 28.9, 'currency' => 'AED'], $offers[0]['price']);
    }

    public function testOnlyWhatTheWebsiteWouldListIsOffered(): void
    {
        //  2287 fails the storefront gate; 1's seller is pending (no summary).
        $finder = $this->finder(gateDrops: [2287], sellers: [14 => $this->seller(14, 'hassan1')]);

        self::assertSame([], $finder->offers([2150], self::STORE, $this->context())[2150]);
        self::assertSame(['2150'], array_column($finder->offers([1], self::STORE, $this->context())[1], 'sku'));
    }

    public function testADeletedSellerShownAsHubMarketNeverOffersAnything(): void
    {
        $hubMarket = ['code' => null, 'vendor_entity_id' => null, 'name' => 'Hub Market', 'is_marketplace' => true];
        $finder = $this->finder(sellers: [4 => $hubMarket, 14 => $this->seller(14, 'hassan1'), 1 => $this->seller(1, 'test_1')]);

        self::assertSame(['2150'], array_column($finder->offers([1], self::STORE, $this->context())[1], 'sku'));
    }

    public function testAnOfferOutOfStockIsNotListed(): void
    {
        $finder = $this->finder(outOfStock: [2287]);

        self::assertSame(['2150'], array_column($finder->offers([1], self::STORE, $this->context())[1], 'sku'));
    }

    public function testAProductNobodyCopiedHasNoOffersAndCostsNoLookups(): void
    {
        $gate = $this->createMock(OfferGate::class);
        $gate->expects(self::never())->method('passing');
        $products = $this->createMock(OfferProducts::class);
        $products->expects(self::never())->method('load');

        $finder = $this->finder(gate: $gate, products: $products);

        self::assertSame([7 => []], $finder->offers([7], self::STORE, $this->context()));
        self::assertSame([], $finder->cacheTags([7], self::STORE));
    }

    public function testEveryProductOfAResponseIsServedByOneBatchAndOnceARequest(): void
    {
        $families = $this->createMock(FamilyReader::class);
        $families->expects(self::once())->method('families')->with([1, 2146, 7])->willReturnCallback(
            static fn (array $ids): array => FamilyReader::group($ids, [2150 => 1, 2287 => 1, 2146 => 2244])
        );
        $gate = $this->gate([]);
        $gate->expects(self::once())->method('passing');
        $sellers = $this->sellers($this->allSellers());
        $sellers->expects(self::once())->method('getByVendorIds');
        $products = $this->products([]);
        $products->expects(self::once())->method('load')->with(
            self::callback(static function (array $ids): bool {
                sort($ids);

                return $ids === [1, 2146, 2150, 2244, 2287];
            })
        );

        $finder = $this->finder(families: $families, gate: $gate, products: $products, sellerProvider: $sellers);
        $first = $finder->offers([1, 2146, 7], self::STORE, $this->context());
        $again = $finder->offers([2146, 1], self::STORE, $this->context());

        self::assertSame(['2287', '2150'], array_column($first[1], 'sku'));
        self::assertSame([], $first[2146], 'the main product 2244 has no price in the store view: nothing to offer');
        self::assertSame([], $first[7]);
        self::assertSame($first[1], $again[1]);
    }

    public function testEqualPricesKeepTheOlderProductFirst(): void
    {
        $offers = OfferFinder::cheapestFirst([
            ['sku' => 'b', 'price' => ['value' => 10.0], '_product_id' => 9],
            ['sku' => 'c', 'price' => ['value' => 5.0], '_product_id' => 12],
            ['sku' => 'a', 'price' => ['value' => 10.0], '_product_id' => 3],
        ]);

        self::assertSame(['c', 'a', 'b'], array_column($offers, 'sku'));
    }

    public function testTheCacheTagsCoverTheWholeFamilyAndEveryOfferingSeller(): void
    {
        $finder = $this->finder(gateDrops: [1]);
        $finder->offers([2150], self::STORE, $this->context());

        $tags = $finder->cacheTags([2150], self::STORE);
        sort($tags);

        self::assertSame(['cat_p_1', 'cat_p_2150', 'cat_p_2287', 'hm_vendor_4'], $tags);
    }

    public function testTheResetForgetsTheRequest(): void
    {
        $families = $this->createMock(FamilyReader::class);
        $families->expects(self::exactly(2))->method('families')->willReturn([7 => [7]]);
        $finder = $this->finder(families: $families);

        $finder->offers([7], self::STORE, $this->context());
        $finder->_resetState();
        $finder->offers([7], self::STORE, $this->context());
    }

    /**
     * @param int[] $gateDrops
     * @param array<int, array<string, mixed>>|null $sellers vendor id => summary
     * @param int[] $outOfStock
     */
    private function finder(
        array $gateDrops = [],
        ?array $sellers = null,
        array $outOfStock = [],
        ?FamilyReader $families = null,
        ?OfferGate $gate = null,
        ?OfferProducts $products = null,
        ?SellerSummaryProviderInterface $sellerProvider = null
    ): OfferFinder {
        if ($families === null) {
            $families = $this->createMock(FamilyReader::class);
            $families->method('families')->willReturnCallback(
                static fn (array $ids): array => FamilyReader::group($ids, [2150 => 1, 2287 => 1, 2146 => 2244])
            );
        }
        $dispatch = $this->createMock(DispatchTimeReader::class);
        $dispatch->method('forVendors')->willReturnCallback(
            static fn (array $vendorIds): array => in_array(4, $vendorIds, true)
                ? [4 => ['code' => 'days_2_3', 'label' => '2-3 days', 'source' => 'DECLARED']]
                : []
        );

        return new OfferFinder(
            $families,
            $gate ?? $this->gate($gateDrops),
            $products ?? $this->products($outOfStock),
            $sellerProvider ?? $this->sellers($sellers ?? $this->allSellers()),
            $dispatch
        );
    }

    /**
     * @param int[] $drops
     */
    private function gate(array $drops): OfferGate
    {
        $gate = $this->createMock(OfferGate::class);
        $gate->method('passing')->willReturnCallback(static function (array $ids) use ($drops): array {
            $out = [];
            foreach ($ids as $id) {
                if (isset(self::CATALOGUE[$id]) && !in_array($id, $drops, true)) {
                    $out[$id] = ['vendor_id' => self::CATALOGUE[$id][0], 'type_id' => self::CATALOGUE[$id][1]];
                }
            }

            return $out;
        });

        return $gate;
    }

    /**
     * @param int[] $outOfStock
     */
    private function products(array $outOfStock): OfferProducts
    {
        $products = $this->createMock(OfferProducts::class);
        $products->method('load')->willReturnCallback(static function (array $ids) use ($outOfStock): array {
            $out = [];
            foreach ($ids as $id) {
                if (!isset(self::PRICES[$id])) {
                    continue;
                }
                $out[$id] = [
                    'uid' => base64_encode((string) $id),
                    'sku' => $id === 1 ? '24-MB01' : (string) $id,
                    'url_key' => 'key-' . $id,
                    'type_id' => self::CATALOGUE[$id][1],
                    'in_stock' => !in_array($id, $outOfStock, true),
                    'price' => ['value' => self::PRICES[$id][0], 'currency' => 'AED'],
                    'regular_price' => ['value' => self::PRICES[$id][1], 'currency' => 'AED'],
                ];
            }

            return $out;
        });

        return $products;
    }

    /**
     * @param array<int, array<string, mixed>> $summaries
     */
    private function sellers(array $summaries): SellerSummaryProviderInterface
    {
        $sellers = $this->createMock(SellerSummaryProviderInterface::class);
        $sellers->method('getByVendorIds')->willReturnCallback(
            static fn (array $vendorIds): array => array_intersect_key($summaries, array_flip($vendorIds))
        );

        return $sellers;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function allSellers(): array
    {
        return [
            1 => $this->seller(1, 'test_1'),
            2 => $this->seller(2, 'luma'),
            4 => $this->seller(4, 'ENARA'),
            14 => $this->seller(14, 'hassan1'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function seller(int $vendorId, string $code): array
    {
        return [
            'code' => $code,
            'vendor_entity_id' => $vendorId,
            'name' => $code,
            'logo_url' => null,
            'rating' => null,
            'review_count' => 0,
            'product_count' => 1,
            'is_marketplace' => false,
            'link' => null,
        ];
    }

    private function context(): ContextInterface
    {
        return $this->createMock(ContextInterface::class);
    }
}
