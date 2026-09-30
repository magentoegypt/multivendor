<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Test\Unit\Model\Credit;

use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use MagentoEgypt\HubAppAccount\Model\Credit\CreditAccountReader;
use MagentoEgypt\HubAppAccount\Model\Credit\CreditApplier;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Vnecoms\Credit\Api\CreditManagementInterface;

/**
 * hmApplyStoreCredit: only on the caller's own cart, only for a customer group allowed to spend credit,
 * never more than the balance.
 */
final class CreditApplierTest extends TestCase
{
    private const CUSTOMER = 5;
    private const CART_ID = 7;

    /** @var GetCartForUser&MockObject */
    private GetCartForUser $carts;

    /** @var CreditManagementInterface&MockObject */
    private CreditManagementInterface $credit;

    /** @var CreditAccountReader&MockObject */
    private CreditAccountReader $account;

    protected function setUp(): void
    {
        $this->carts = $this->createMock(GetCartForUser::class);
        $this->credit = $this->createMock(CreditManagementInterface::class);
        $this->account = $this->createMock(CreditAccountReader::class);
    }

    public function testOnlyTheCallersOwnCartIsTouched(): void
    {
        $this->carts->expects(self::once())->method('execute')->with('masked', self::CUSTOMER, 1)
            ->willThrowException(new GraphQlAuthorizationException(__('The current user cannot perform operations on cart "masked"')));
        $this->credit->expects(self::never())->method('set');

        $this->expectException(GraphQlAuthorizationException::class);
        $this->applier()->apply('masked', 10.0, self::CUSTOMER, 1);
    }

    public function testACustomerGroupNotAllowedCreditCannotApplyIt(): void
    {
        $this->carts->method('execute')->willReturn($this->cart());
        $this->account->method('canUse')->with(self::CUSTOMER)->willReturn(false);
        $this->account->method('balance')->willReturn(500.0);
        $this->credit->expects(self::never())->method('set');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Store credit can\'t be used with your account.');
        $this->applier()->apply('masked', 10.0, self::CUSTOMER, 1);
    }

    public function testMoreThanTheBalanceIsRefused(): void
    {
        $this->carts->method('execute')->willReturn($this->cart());
        $this->account->method('canUse')->willReturn(true);
        $this->account->method('balance')->with(self::CUSTOMER)->willReturn(50.0);
        $this->credit->expects(self::never())->method('set');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('You can use at most 50.00 AED of store credit.');
        $this->applier()->apply('masked', 50.01, self::CUSTOMER, 1);
    }

    public function testTheWholeBalanceCanBeApplied(): void
    {
        $cart = $this->cart();
        $this->carts->method('execute')->willReturn($cart);
        $this->account->method('canUse')->willReturn(true);
        $this->account->method('balance')->willReturn(50.0);
        $this->credit->expects(self::once())->method('set')->with(self::CART_ID, 50.0);

        self::assertSame($cart, $this->applier($cart)->apply('masked', 50.0, self::CUSTOMER, 1));
    }

    public function testTheBalanceIsComparedInTheBaseCurrency(): void
    {
        //  1 base unit = 2 cart units: 40 in the cart's currency is 20 of a balance of 20.
        $cart = $this->cart(2.0, 'USD');
        $this->carts->method('execute')->willReturn($cart);
        $this->account->method('canUse')->willReturn(true);
        $this->account->method('balance')->willReturn(20.0);
        $this->credit->expects(self::once())->method('set')->with(self::CART_ID, 20.0);

        $this->applier($cart)->apply('masked', 40.0, self::CUSTOMER, 1);

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('You can use at most 40.00 USD of store credit.');
        $this->applier($cart)->apply('masked', 40.02, self::CUSTOMER, 1);
    }

    public function testOnlyAPositiveAmountIsAccepted(): void
    {
        $this->carts->expects(self::never())->method('execute');
        $this->credit->expects(self::never())->method('set');

        foreach ([0.0, -5.0, NAN, INF] as $amount) {
            try {
                $this->applier()->apply('masked', $amount, self::CUSTOMER, 1);
                self::fail('Accepted ' . var_export($amount, true));
            } catch (GraphQlInputException $e) {
                self::assertSame('Enter an amount of store credit greater than 0.', $e->getMessage());
            }
        }
    }

    public function testAnEmptyCartTakesNoCredit(): void
    {
        $this->carts->method('execute')->willReturn($this->cart(1.0, 'AED', 0));
        $this->account->method('canUse')->willReturn(true);
        $this->credit->expects(self::never())->method('set');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Your cart is empty.');
        $this->applier()->apply('masked', 10.0, self::CUSTOMER, 1);
    }

    private function applier(?Quote $recollected = null): CreditApplier
    {
        $repository = $this->createMock(CartRepositoryInterface::class);
        $repository->method('get')->with(self::CART_ID)->willReturn($recollected ?? $this->cart());

        return new CreditApplier(
            $this->carts,
            $this->credit,
            $this->account,
            $repository,
            $this->createMock(LoggerInterface::class)
        );
    }

    private function cart(float $rate = 1.0, string $currency = 'AED', int $items = 2): Quote
    {
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        $quote->setData([
            'id' => self::CART_ID,
            'items_count' => $items,
            'base_to_quote_rate' => $rate,
            'quote_currency_code' => $currency,
        ]);

        return $quote;
    }
}
