<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Test\Unit\Model\Resolver;

use Magento\Catalog\Model\Product;
use Magento\Framework\Data\Collection;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Cart\AddProductsToCartError;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteMutexInterface;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use MagentoEgypt\HubAppAccount\Model\Credit\TopUpCatalog;
use MagentoEgypt\HubAppAccount\Model\Credit\TopUpOptions;
use MagentoEgypt\HubAppAccount\Model\Resolver\AddCreditToCart;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * hmAddCreditToCart: only on the caller's own cart, only an amount on offer, the buy request the
 * website's product page posts, the cart untouched when anything is refused.
 */
final class AddCreditToCartTest extends TestCase
{
    private const CUSTOMER = 5;
    private const STORE = 1;

    /** @var GetCartForUser&MockObject */
    private GetCartForUser $carts;

    /** @var QuoteMutexInterface&MockObject */
    private QuoteMutexInterface $mutex;

    /** @var TopUpCatalog&MockObject */
    private TopUpCatalog $catalog;

    /** @var CartRepositoryInterface&MockObject */
    private CartRepositoryInterface $cartRepository;

    /** @var Quote&MockObject */
    private Quote $cart;

    /** @var Collection&MockObject */
    private Collection $items;

    protected function setUp(): void
    {
        $this->carts = $this->createMock(GetCartForUser::class);
        $this->mutex = $this->createMock(QuoteMutexInterface::class);
        $this->mutex->method('execute')->willReturnCallback(
            static fn (array $ids, callable $callable, array $args = []) => $callable(...$args)
        );
        $this->catalog = $this->createMock(TopUpCatalog::class);
        $this->catalog->method('currency')->willReturn('AED');
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->items = $this->createMock(Collection::class);
        $this->cart = $this->createMock(Quote::class);
        $this->cart->method('getStoreId')->willReturn(self::STORE);
        $this->cart->method('getItemsCollection')->willReturn($this->items);
    }

    private function resolver(): AddCreditToCart
    {
        $objects = $this->createMock(DataObjectFactory::class);
        $objects->method('create')->willReturnCallback(
            static fn (array $arguments = []) => new DataObject($arguments['data'] ?? [])
        );

        return new AddCreditToCart(
            $this->carts,
            $this->mutex,
            $this->catalog,
            $this->cartRepository,
            new AddProductsToCartError(['Product that you are trying to add is not available.' => 'NOT_SALABLE']),
            $objects,
            new NullLogger()
        );
    }

    /**
     * @param list<array<string, mixed>> $products
     */
    private function selling(array $products): void
    {
        $this->carts->method('execute')->with('masked', self::CUSTOMER, self::STORE)->willReturn($this->cart);
        $this->catalog->method('forStore')->with(self::STORE)->willReturn(TopUpOptions::fromProducts($products));
    }

    private static function fixed(int $id, string $credit): array
    {
        return ['id' => $id, 'sku' => 'credit-' . $credit, 'credit_type' => '1', 'credit_value_fixed' => $credit, 'credit_price' => $credit];
    }

    private static function custom(int $id): array
    {
        return ['id' => $id, 'sku' => 'credit-any', 'credit_type' => '3', 'credit_value_custom' => ['from' => 10, 'to' => 1000], 'credit_rate' => '1'];
    }

    public function testSomeoneElsesCartIsRefusedBeforeItIsLocked(): void
    {
        $this->carts->method('execute')
            ->willThrowException(new GraphQlAuthorizationException(__('The current user cannot perform operations on cart "masked"')));
        $this->mutex->expects(self::never())->method('execute');
        $this->cart->expects(self::never())->method('addProduct');

        $this->expectException(GraphQlAuthorizationException::class);
        $this->resolver()->add('masked', self::CUSTOMER, self::STORE, 100);
    }

    public function testAPresetIsBoughtWithItsOwnProductAsThePagePostsIt(): void
    {
        $this->selling([self::fixed(11, '50'), self::fixed(12, '100'), self::custom(13)]);
        $product = $this->createMock(Product::class);
        $this->catalog->expects(self::once())->method('productForCart')->with(12, self::STORE)->willReturn($product);
        $this->cart->expects(self::once())->method('addProduct')
            ->with($product, self::callback(static fn (DataObject $request): bool => $request->getData() === ['product' => 12, 'qty' => 1]))
            ->willReturn($this->createMock(Quote\Item::class));
        $this->cartRepository->expects(self::once())->method('save')->with($this->cart);

        $output = $this->resolver()->add('masked', self::CUSTOMER, self::STORE, 100);

        self::assertSame([], $output['user_errors']);
        self::assertSame($this->cart, $output['cart']['model']);
    }

    public function testACustomAmountIsBoughtAsAWholeCreditValue(): void
    {
        $this->selling([self::fixed(11, '50'), self::custom(13)]);
        $product = $this->createMock(Product::class);
        $this->catalog->method('productForCart')->with(13, self::STORE)->willReturn($product);
        $this->cart->expects(self::once())->method('addProduct')
            ->with($product, self::callback(static fn (DataObject $request): bool => $request->getData() === [
                'product' => 13,
                'qty' => 1,
                'store_credit' => ['credit_value' => 150],
            ]));
        $this->cartRepository->expects(self::once())->method('save');

        self::assertSame([], $this->resolver()->add('masked', self::CUSTOMER, self::STORE, 150.0)['user_errors']);
    }

    public function testAnAmountNotOnOfferChangesNothing(): void
    {
        $cases = [
            'presets only' => [[self::fixed(11, '50'), self::fixed(12, '100')], 75.0, 'Choose one of the credit amounts on offer.'],
            'custom only' => [[self::custom(13)], 5.0, 'Enter a whole amount from AED 10 to AED 1,000.'],
            'both' => [
                [self::fixed(11, '50'), self::custom(13)],
                12.5,
                'Choose one of the credit amounts on offer, or a whole amount from AED 10 to AED 1,000.',
            ],
        ];
        foreach ($cases as $name => [$products, $amount, $message]) {
            $this->setUp();
            $this->selling($products);
            $this->catalog->expects(self::never())->method('productForCart');
            $this->cart->expects(self::never())->method('addProduct');
            $this->cartRepository->expects(self::never())->method('save');

            $output = $this->resolver()->add('masked', self::CUSTOMER, self::STORE, $amount);

            self::assertSame(
                [['code' => 'INVALID_PARAMETER_VALUE', 'message' => $message, 'path' => [0], 'quantity' => null]],
                $output['user_errors'],
                $name
            );
        }
    }

    public function testNothingToBuyWhenTheStoreSellsNoCredit(): void
    {
        $this->selling([]);
        $this->cart->expects(self::never())->method('addProduct');
        $this->cartRepository->expects(self::never())->method('save');

        $output = $this->resolver()->add('masked', self::CUSTOMER, self::STORE, 100);

        self::assertSame('PRODUCT_NOT_FOUND', $output['user_errors'][0]['code']);
        self::assertSame('Store credit can\'t be bought at the moment.', $output['user_errors'][0]['message']);
    }

    public function testAProductNoLongerOnSaleIsNotAdded(): void
    {
        $this->selling([self::fixed(12, '100')]);
        $this->catalog->method('productForCart')->willReturn(null);
        $this->cart->expects(self::never())->method('addProduct');
        $this->cartRepository->expects(self::never())->method('save');

        self::assertSame(
            'PRODUCT_NOT_FOUND',
            $this->resolver()->add('masked', self::CUSTOMER, self::STORE, 100)['user_errors'][0]['code']
        );
    }

    public function testTheCartsRefusalIsAUserErrorAndTheCartIsLeftAsItWas(): void
    {
        $this->selling([self::fixed(12, '100')]);
        $this->catalog->method('productForCart')->willReturn($this->createMock(Product::class));
        $this->cart->method('addProduct')->willReturn('Product that you are trying to add is not available.');
        $this->items->expects(self::once())->method('clear');
        $this->cartRepository->expects(self::never())->method('save');

        $output = $this->resolver()->add('masked', self::CUSTOMER, self::STORE, 100);

        self::assertSame('NOT_SALABLE', $output['user_errors'][0]['code']);
        self::assertSame('Product that you are trying to add is not available.', $output['user_errors'][0]['message']);
    }

    public function testExceptionsWhileAddingAreUserErrors(): void
    {
        foreach ([
            [new LocalizedException(__('You need to choose options for your item.')), 'You need to choose options for your item.'],
            [new \RuntimeException('SQLSTATE[HY000]'), 'We couldn\'t add the credit to your cart. Please try again.'],
        ] as [$exception, $message]) {
            $this->setUp();
            $this->selling([self::fixed(12, '100')]);
            $this->catalog->method('productForCart')->willReturn($this->createMock(Product::class));
            $this->cart->method('addProduct')->willThrowException($exception);
            $this->cartRepository->expects(self::never())->method('save');

            $output = $this->resolver()->add('masked', self::CUSTOMER, self::STORE, 100);

            self::assertSame($message, $output['user_errors'][0]['message']);
            self::assertSame('UNDEFINED', $output['user_errors'][0]['code']);
        }
    }
}
