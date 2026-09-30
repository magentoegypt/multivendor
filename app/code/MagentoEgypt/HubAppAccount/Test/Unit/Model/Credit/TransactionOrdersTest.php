<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Test\Unit\Model\Credit;

use MagentoEgypt\HubAppAccount\Model\Credit\CreditOrderLookup;
use MagentoEgypt\HubAppAccount\Model\Credit\TransactionOrders;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * HmStoreCreditTransaction.order_number: the order Vnecoms recorded in additional_info, and only when the
 * customer placed it.
 */
final class TransactionOrdersTest extends TestCase
{
    private const CUSTOMER = 9;

    public function testWhatVnecomsRecordsIsRead(): void
    {
        self::assertSame(['kind' => 'order', 'id' => 42], TransactionOrders::parse('order|42'));
        self::assertSame(['kind' => 'invoice', 'id' => 7], TransactionOrders::parse('invoice|7'));
        self::assertSame(['kind' => 'creditmemo', 'id' => 3], TransactionOrders::parse(' creditmemo|3 '));
        self::assertSame(['kind' => 'vendor_order', 'id' => 15], TransactionOrders::parse('vendor_order|15'));
    }

    public function testRecordsThatNameNoOrderOfTheCustomerAreIgnored(): void
    {
        foreach ([
            null,
            '',
            'order|',
            'order|0',
            'order|-4',
            'order|4|5',
            'order|4 OR 1=1',
            //  A seller's ledger lines: another customer's order.
            'vendor_invoice|1',
            'invoice_item|4',
            'creditmemo_item|2',
            'withdrawal|3',
        ] as $info) {
            self::assertNull(TransactionOrders::parse($info), var_export($info, true));
        }
    }

    public function testEveryRecordLeadsToItsOrderNumber(): void
    {
        $lookup = new FakeOrderLookup(
            [
                'invoice' => [7 => 102],
                'creditmemo' => [3 => 103],
                'vendor_order' => [15 => 104],
            ],
            [101 => '000000101', 102 => '000000102', 103 => '000000103', 104 => '000000104']
        );

        $numbers = (new TransactionOrders($lookup, new NullLogger()))->numbers([
            1 => 'order|101',
            2 => 'invoice|7',
            3 => 'creditmemo|3',
            4 => 'vendor_order|15',
            5 => '',
            6 => null,
        ], self::CUSTOMER);

        self::assertSame(
            [1 => '000000101', 2 => '000000102', 3 => '000000103', 4 => '000000104'],
            $numbers
        );
        self::assertSame([101, 102, 103, 104], $lookup->askedOrders);
        self::assertSame(self::CUSTOMER, $lookup->askedCustomer);
    }

    public function testAnotherCustomersOrderIsNeverNamed(): void
    {
        //  A seller's refund line names the buyer's credit memo; the buyer's order is not the seller's.
        $lookup = new FakeOrderLookup(['creditmemo' => [8 => 300]], [101 => '000000101']);

        $numbers = (new TransactionOrders($lookup, new NullLogger()))->numbers([
            1 => 'creditmemo|8',
            2 => 'order|101',
        ], self::CUSTOMER);

        self::assertSame([2 => '000000101'], $numbers);
    }

    public function testNothingToLookUpAsksNothing(): void
    {
        $lookup = new FakeOrderLookup([], []);

        self::assertSame([], (new TransactionOrders($lookup, new NullLogger()))->numbers([1 => '', 2 => 'x|1'], self::CUSTOMER));
        self::assertNull($lookup->askedCustomer);
    }

    public function testAFailedLookupLeavesTheNumbersOut(): void
    {
        $lookup = new FakeOrderLookup([], []);
        $lookup->fail = true;

        self::assertSame([], (new TransactionOrders($lookup, new NullLogger()))->numbers([1 => 'invoice|2'], self::CUSTOMER));
    }
}

/**
 * CreditOrderLookup over arrays: record id => order id per kind, and the customer's own orders.
 */
class FakeOrderLookup extends CreditOrderLookup
{
    /** @var int[] */
    public array $askedOrders = [];

    public ?int $askedCustomer = null;

    public bool $fail = false;

    /**
     * @param array<string, array<int, int>> $orderIds
     * @param array<int, string> $ownNumbers the customer's orders: id => number
     */
    public function __construct(private readonly array $orderIds, private readonly array $ownNumbers)
    {
    }

    public function orderIds(string $kind, array $ids): array
    {
        if ($this->fail) {
            throw new \RuntimeException('database gone');
        }

        return array_intersect_key($this->orderIds[$kind] ?? [], array_flip($ids));
    }

    public function ownOrderNumbers(array $orderIds, int $customerId): array
    {
        $this->askedOrders = $orderIds;
        $this->askedCustomer = $customerId;

        return array_intersect_key($this->ownNumbers, array_flip($orderIds));
    }
}
