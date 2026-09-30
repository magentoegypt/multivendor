<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Test\Unit\Model\Pricing;

use Magento\Bundle\Model\Product\Price as BundlePrice;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Model\Store;
use MagentoEgypt\HubAppBundle\Model\Cart\BundleBuyRequestBuilder;
use MagentoEgypt\HubAppBundle\Model\Cart\BundleSelectionReader;
use MagentoEgypt\HubAppBundle\Model\Cart\MarketplaceGate;
use MagentoEgypt\HubAppBundle\Model\Cart\SuperAttributeKeeper;
use MagentoEgypt\HubAppBundle\Model\Pricing\PackagePricer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @covers \MagentoEgypt\HubAppBundle\Model\Pricing\PackagePricer
 */
class PackagePricerTest extends TestCase
{
    private const BUNDLE_ID = 900;

    /** Selection 11 of option 5 adds product 501; selection 12 of option 6 adds 502. */
    private const SELECTIONS = [
        11 => ['option_id' => 5, 'product_id' => 501, 'type_id' => 'simple'],
        12 => ['option_id' => 6, 'product_id' => 502, 'type_id' => 'simple'],
    ];

    /** @var ProductRepositoryInterface&MockObject */
    private $repository;

    /** @var MarketplaceGate&MockObject */
    private $gate;

    /** @var array<string, mixed>|null the buy request prepareForCartAdvanced() was given */
    private ?array $preparedRequest = null;

    /** @var mixed what prepareForCartAdvanced() answers; a callable gets the product */
    private $prepared;

    private float $finalPrice = 61.0;

    private ?int $pricedGroup = null;

    public function testThePackageIsPricedByTheBundlePriceModelForTheCallersGroup(): void
    {
        $this->prepared = fn (Product $bundle): array => [$bundle, $this->child(501, 32.0, 1.0), $this->child(502, 12.0, 2.0)];

        $quote = $this->pricer()->quote('KIT', 2.0, [self::pick(11), self::pick(12)], $this->store(), 1);

        self::assertTrue($quote['available']);
        self::assertNull($quote['message']);
        self::assertSame(2.0, $quote['quantity']);
        self::assertSame(['value' => 61.0, 'currency' => 'AED'], $quote['price']);
        self::assertSame(['value' => 122.0, 'currency' => 'AED'], $quote['row_total']);
        //  32 x 1 + 12 x 2 = 56 bought separately: the bundle costs more, so no saving is claimed.
        self::assertNull($quote['regular_total']);
        self::assertNull($quote['saving']);
        self::assertSame(1, $this->pricedGroup, 'priced for the caller\'s customer group');
        self::assertSame([self::BUNDLE_ID, 501, 502], $quote[PackagePricer::IDS_KEY]);
        self::assertSame(
            ['product' => self::BUNDLE_ID, 'qty' => 2.0, 'bundle_option' => [5 => 11, 6 => 12]],
            $this->preparedRequest,
            'the same buy request hmAddBundleToCart adds'
        );
    }

    public function testTheSavingComparesTheItemsBoughtSeparately(): void
    {
        $this->finalPrice = 61.0;
        $this->prepared = fn (Product $bundle): array => [
            $bundle,
            $this->child(501, 32.0, 1.0),
            //  A configurable child is compared at its chosen variant's price.
            $this->child(502, 0.0, 2.0, $this->variant(20.0)),
        ];

        $quote = $this->pricer()->quote('KIT', 1.0, [self::pick(11), self::pick(12)], $this->store(), 0);

        self::assertSame(['value' => 72.0, 'currency' => 'AED'], $quote['regular_total']);
        self::assertSame(['value' => 11.0, 'currency' => 'AED'], $quote['saving']);
        self::assertSame(15, $quote['discount_percent']);
    }

    public function testAnUnknownOrHiddenBundleIsNotFound(): void
    {
        $this->repository = $this->createMock(ProductRepositoryInterface::class);
        $this->repository->method('get')->willThrowException(new NoSuchEntityException());
        $quote = $this->pricer()->quote('NOPE', 1.0, [self::pick(11)], $this->store(), 0);
        self::assertFalse($quote['available']);
        self::assertSame('Could not find a product with SKU "NOPE"', $quote['message']);
        self::assertNull($quote['price']);
        self::assertSame([], $quote[PackagePricer::IDS_KEY]);

        $this->repository = null;
        $this->gate = $this->createMock(MarketplaceGate::class);
        $this->gate->method('allowsBundle')->willReturn(false);
        $quote = $this->pricer()->quote('KIT', 1.0, [self::pick(11)], $this->store(), 0);
        self::assertFalse($quote['available'], 'unapproved or its seller inactive');
    }

    public function testAChoiceTheBundleDoesNotHaveIsRefusedBeforePricing(): void
    {
        $this->prepared = static fn (): array => self::fail('never prepared');
        $foreign = base64_encode('bundle/5/77/1');

        $quote = $this->pricer()->quote('KIT', 1.0, [['selection_uid' => $foreign]], $this->store(), 0);

        self::assertFalse($quote['available']);
        self::assertSame('The options you selected are not available.', $quote['message']);
    }

    public function testTheBundleTypesAnswerIsTheReason(): void
    {
        $this->prepared = static fn (): string => 'The required options you selected are not available.';

        $quote = $this->pricer()->quote('KIT', 1.0, [self::pick(11)], $this->store(), 0);

        self::assertFalse($quote['available']);
        self::assertSame('The required options you selected are not available.', $quote['message']);
        self::assertSame([self::BUNDLE_ID, 501], $quote[PackagePricer::IDS_KEY]);
    }

    public function testASelectionOfAnInactiveSellerIsRefused(): void
    {
        $this->gate = $this->createMock(MarketplaceGate::class);
        $this->gate->method('allowsBundle')->willReturn(true);
        $this->gate->method('allowsSelections')->willReturn(false);
        $this->prepared = static fn (): array => self::fail('never prepared');

        $quote = $this->pricer()->quote('KIT', 1.0, [self::pick(11)], $this->store(), 0);

        self::assertFalse($quote['available']);
        self::assertSame('The options you selected are not available.', $quote['message']);
    }

    public function testRegularTotalNeedsEveryPrice(): void
    {
        self::assertNull(PackagePricer::regularTotal([]));
        self::assertSame(44.0, PackagePricer::regularTotal([$this->child(1, 20.0, 1.0), $this->child(2, 12.0, 2.0)]));
        self::assertNull(PackagePricer::regularTotal([$this->child(1, 20.0, 1.0), $this->child(2, 0.0, 1.0)]));
    }

    private function pricer(): PackagePricer
    {
        $reader = $this->createMock(BundleSelectionReader::class);
        $reader->method('forBundle')->willReturn(self::SELECTIONS);

        $gate = $this->gate ?? $this->createMock(MarketplaceGate::class);
        if ($this->gate === null) {
            $gate->method('allowsBundle')->willReturn(true);
            $gate->method('allowsSelections')->willReturn(true);
        }

        $factory = $this->createMock(DataObjectFactory::class);
        $factory->method('create')->willReturnCallback(
            static fn (array $args): DataObject => new DataObject($args['data'] ?? [])
        );

        $currency = $this->createMock(PriceCurrencyInterface::class);
        $currency->method('convert')->willReturnArgument(0);

        return new PackagePricer(
            $this->repository ?? $this->repository(),
            $reader,
            new BundleBuyRequestBuilder(),
            new SuperAttributeKeeper(),
            $gate,
            $factory,
            $currency,
            $this->createMock(LoggerInterface::class)
        );
    }

    /**
     * @return ProductRepositoryInterface&MockObject
     */
    private function repository(): ProductRepositoryInterface
    {
        $type = $this->createMock(BundleType::class);
        $type->method('prepareForCartAdvanced')->willReturnCallback(
            function (DataObject $request, Product $product) {
                $this->preparedRequest = $request->getData();
                $answer = $this->prepared;

                return is_callable($answer) ? $answer($product) : $answer;
            }
        );
        $price = $this->createMock(BundlePrice::class);
        $price->method('getFinalPrice')->willReturnCallback(fn (): float => $this->finalPrice);

        $bundle = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->onlyMethods(['getId', 'getData', 'getWebsiteIds', 'isSaleable', 'isAvailable', 'getTypeInstance', 'getPriceModel'])
            ->addMethods(['setCustomerGroupId'])
            ->getMock();
        $bundle->method('getId')->willReturn(self::BUNDLE_ID);
        $bundle->method('getData')->willReturnCallback(
            static fn ($key = '') => $key === 'type_id' ? 'new_bundle' : null
        );
        $bundle->method('getWebsiteIds')->willReturn([1]);
        $bundle->method('isSaleable')->willReturn(true);
        $bundle->method('isAvailable')->willReturn(true);
        $bundle->method('getTypeInstance')->willReturn($type);
        $bundle->method('getPriceModel')->willReturn($price);
        $bundle->method('setCustomerGroupId')->willReturnCallback(function ($group) use ($bundle) {
            $this->pricedGroup = (int) $group;

            return $bundle;
        });

        $repository = $this->createMock(ProductRepositoryInterface::class);
        $repository->method('get')->willReturn($bundle);

        return $repository;
    }

    /**
     * A prepared bundle child: its regular price, its quantity in one package.
     */
    private function child(int $id, float $price, float $qty, ?Product $variant = null): Product
    {
        $child = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getData', 'getCustomOption'])
            ->addMethods(['getCartQty'])
            ->getMock();
        $child->method('getId')->willReturn($id);
        $child->method('getData')->willReturnCallback(static fn ($key = '') => $key === 'price' ? $price : null);
        $child->method('getCartQty')->willReturn($qty);
        $option = null;
        if ($variant !== null) {
            //  The quote item option a configurable child carries (simple_product).
            $option = $this->getMockBuilder(DataObject::class)->addMethods(['getProduct'])->getMock();
            $option->method('getProduct')->willReturn($variant);
        }
        $child->method('getCustomOption')->willReturn($option);

        return $child;
    }

    private function variant(float $price): Product
    {
        $variant = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getData'])
            ->getMock();
        $variant->method('getData')->willReturnCallback(static fn ($key = '') => $key === 'price' ? $price : null);

        return $variant;
    }

    private function store(): Store
    {
        $store = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId', 'getWebsiteId', 'getCurrentCurrencyCode'])
            ->getMock();
        $store->method('getId')->willReturn(1);
        $store->method('getWebsiteId')->willReturn(1);
        $store->method('getCurrentCurrencyCode')->willReturn('AED');

        return $store;
    }

    /**
     * @return array{selection_uid: string}
     */
    private static function pick(int $selectionId): array
    {
        $optionId = self::SELECTIONS[$selectionId]['option_id'];

        return ['selection_uid' => base64_encode("bundle/{$optionId}/{$selectionId}/1")];
    }
}
