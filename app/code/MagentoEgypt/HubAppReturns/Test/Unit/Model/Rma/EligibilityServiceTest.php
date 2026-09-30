<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Model\Rma;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use MagentoEgypt\HubAppReturns\Model\Rma\EligibilityService;
use MagentoEgypt\HubAppReturns\Model\Rma\LabelReader;
use MagentoEgypt\HubAppReturns\Model\Rma\OrderLineReader;
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnableQty;
use PHPUnit\Framework\TestCase;
use Vnecoms\RMA\Helper\Config as RmaConfig;

/**
 * hmCreateReturn's checks (EligibilityService::prepare): the order is the signed-in customer's, every
 * line is a line of that order, one seller per return, and a custom refund no larger than the lines cost.
 */
final class EligibilityServiceTest extends TestCase
{
    private const CUSTOMER = 5;
    private const ORDER_ID = 12;
    private const NUMBER = '000000012';

    /** @var array<int, array{0: string, 1: mixed}> where() calls of the queries */
    private array $where = [];

    public function testTheOrderIsLookedUpAmongTheSignedInCustomersOnly(): void
    {
        //  No row: the number is not an order of customer 5 (someone else's, or none at all).
        $service = $this->service(null, [], never: true);

        try {
            $service->prepare(self::CUSTOMER, 1, $this->input([101 => 1]));
            self::fail('Another customer\'s order was accepted.');
        } catch (GraphQlNoSuchEntityException $e) {
            self::assertSame('We couldn\'t find order 000000012 in your account.', $e->getMessage());
        }
        self::assertContains(['increment_id = ?', self::NUMBER], $this->where);
        self::assertContains(['customer_id = ?', self::CUSTOMER], $this->where);
    }

    public function testALineOfAnotherOrderIsRefused(): void
    {
        $service = $this->service($this->order(), [101 => $this->line(101, 3)]);

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Item 999 is not a line of order 000000012.');
        $service->prepare(self::CUSTOMER, 1, $this->input([101 => 1, 999 => 1]));
    }

    public function testItemsOfTwoSellersNeedSeparateReturns(): void
    {
        $service = $this->service($this->order(), [101 => $this->line(101, 3), 102 => $this->line(102, 4)]);

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Items sold by different sellers need separate returns.');
        $service->prepare(self::CUSTOMER, 1, $this->input([101 => 1, 102 => 1]));
    }

    public function testHubMarketsOwnLinesCountAsOneSeller(): void
    {
        $service = $this->service($this->order(), [101 => $this->line(101, 0), 102 => $this->line(102, 0)]);

        $prepared = $service->prepare(self::CUSTOMER, 1, $this->input([101 => 1, 102 => 2]));

        self::assertSame(
            [['item_id' => 101, 'item_qty' => 1], ['item_id' => 102, 'item_qty' => 2]],
            $prepared['items']
        );
    }

    public function testACustomRefundCannotExceedWhatTheLinesCost(): void
    {
        //  (row total incl. tax 200 - discount 20) / 2 ordered = 90 a unit; 2 returned = 180.
        $lines = [101 => $this->line(101, 3)];

        try {
            $this->service($this->order(), $lines)->prepare(self::CUSTOMER, 1, $this->input([101 => 2], 180.01));
            self::fail('A refund above the lines\' cost was accepted.');
        } catch (GraphQlInputException $e) {
            self::assertSame('The refund can\'t be more than 180.00 AED for these items.', $e->getMessage());
        }

        $prepared = $this->service($this->order(), $lines)->prepare(self::CUSTOMER, 1, $this->input([101 => 2], 180.0));
        self::assertSame('custom_amount', $prepared['refund_amount_type']);
        self::assertSame(180.0, $prepared['refund_custom_amount']);
        self::assertSame(self::NUMBER, $prepared['order']['increment_id']);
    }

    public function testQuantitiesHeldInOpenReturnsCannotBeReturnedAgain(): void
    {
        //  Both units are in a pending return; the cancelled one does not count.
        $held = [
            ['order_item_id' => 101, 'qty' => 2, 'increment_id' => 'R1', 'state' => 'pending'],
            ['order_item_id' => 101, 'qty' => 2, 'increment_id' => 'R0', 'state' => 'canceled'],
        ];
        $service = $this->service($this->order(), [101 => $this->line(101, 3)], $held);

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('"Blender" can\'t be returned: it isn\'t invoiced yet, or it is already in a return.');
        $service->prepare(self::CUSTOMER, 1, $this->input([101 => 1]));
    }

    /**
     * @param array<string, mixed>|null $order the sales_order row the customer-scoped query finds
     * @param array<int, array<string, mixed>> $lines every line of that order
     * @param array<int, array<string, mixed>> $held ves_rma_request_item rows of those lines
     */
    private function service(?array $order, array $lines, array $held = [], bool $never = false): EligibilityService
    {
        $select = $this->createMock(Select::class);
        foreach (['from', 'join', 'order', 'limit'] as $method) {
            $select->method($method)->willReturnSelf();
        }
        $select->method('where')->willReturnCallback(function (string $condition, $value = null) use ($select) {
            $this->where[] = [$condition, $value];

            return $select;
        });
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchRow')->willReturn($order ?? false);
        $connection->method('fetchAll')->willReturn($held);
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        //  refundPerUnit() is the real formula; only the query is replaced.
        $reader = $this->getMockBuilder(OrderLineReader::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['linesOfOrders'])
            ->getMock();
        if ($never) {
            $reader->expects(self::never())->method('linesOfOrders');
        } else {
            //  Only the lines of the customer's order are candidates.
            $reader->method('linesOfOrders')->with([self::ORDER_ID])->willReturn($lines);
        }

        $labels = $this->createMock(LabelReader::class);
        $labels->method('reasons')->willReturn([1 => ['id' => 1, 'label' => 'Damaged', 'active' => true]]);
        $config = $this->createMock(RmaConfig::class);
        $config->method('allowPerOrder')->willReturn(true);
        $config->method('allowOtherReasons')->willReturn(true);
        $config->method('enableReasons')->willReturn(true);

        return new EligibilityService($resource, new ReturnableQty(), $reader, $labels, $config);
    }

    /**
     * @return array<string, mixed>
     */
    private function order(): array
    {
        return [
            'entity_id' => (string) self::ORDER_ID,
            'increment_id' => self::NUMBER,
            'status' => 'complete',
            'state' => 'complete',
            'customer_email' => 'mona@example.com',
            'order_currency_code' => 'AED',
            'store_id' => '1',
        ];
    }

    /**
     * A shipped and invoiced top-level line: 2 ordered, 200 incl. tax, 20 discount.
     *
     * @return array<string, mixed>
     */
    private function line(int $itemId, int $vendorId): array
    {
        return [
            'item_id' => (string) $itemId,
            'order_id' => (string) self::ORDER_ID,
            'parent_item_id' => null,
            'product_id' => '40',
            'product_type' => 'simple',
            'sku' => 'BL-' . $itemId,
            'name' => 'Blender',
            'product_options' => null,
            'vendor_id' => (string) $vendorId,
            'qty_ordered' => '2.0000',
            'qty_shipped' => '2.0000',
            'qty_invoiced' => '2.0000',
            'qty_refunded' => '0.0000',
            'row_total_incl_tax' => '200.0000',
            'discount_amount' => '20.0000',
        ];
    }

    /**
     * @param array<int, int> $quantities order item id => quantity
     * @return array<string, mixed> HmCreateReturnInput
     */
    private function input(array $quantities, ?float $customRefund = null): array
    {
        $items = [];
        foreach ($quantities as $itemId => $qty) {
            $items[] = ['order_item_id' => $itemId, 'quantity' => $qty];
        }
        $input = [
            'order_number' => '#' . self::NUMBER,
            'items' => $items,
            'reason_id' => 1,
            'comment' => 'It arrived broken.',
        ];
        if ($customRefund !== null) {
            $input['refund_amount_type'] = 'CUSTOM';
            $input['refund_custom_amount'] = $customRefund;
        }

        return $input;
    }
}
