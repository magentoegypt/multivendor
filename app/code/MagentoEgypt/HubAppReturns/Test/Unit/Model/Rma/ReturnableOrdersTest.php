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
use MagentoEgypt\HubAppReturns\Model\Rma\EligibilityService;
use MagentoEgypt\HubAppReturns\Model\Rma\LabelReader;
use MagentoEgypt\HubAppReturns\Model\Rma\OrderLineReader;
use MagentoEgypt\HubAppReturns\Model\Rma\Paging;
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnableQty;
use PHPUnit\Framework\TestCase;
use Vnecoms\RMA\Helper\Config as RmaConfig;

/**
 * hmReturnableOrders and hmReturnableOrder: each line the website's form offers carries what it was
 * paid, as the refund cap computes it (row total incl. tax - discount) / qty ordered, a core bundle's
 * child lines with their own figures; one order is looked up among the caller's own, and only offered
 * with something left to return.
 */
final class ReturnableOrdersTest extends TestCase
{
    private const CUSTOMER = 5;
    private const ORDER_ID = 12;

    /** @var array<int, array{0: string, 1: mixed}> where() calls of the queries */
    private array $where = [];

    public function testEveryOfferedLineCarriesItsPriceAndTheMostItsRefundCanBe(): void
    {
        //  Line 104 has one of its two units in return R7 already.
        $held = [['order_item_id' => 104, 'qty' => 1, 'increment_id' => 'R7', 'state' => 'open']];
        $result = $this->service([$this->order()], $this->lines(), $held)
            ->returnableOrders(self::CUSTOMER, 1, Paging::fromArgs(null, 10, 20));

        self::assertSame(1, $result['total']);
        $rows = [];
        foreach ($result['items'][0]['items'] as $row) {
            $rows[$row['order_item_id']] = $row;
        }
        //  The bundle line itself (102) is not offered: its child lines are.
        self::assertSame([101, 103, 104], array_keys($rows));

        //  200 incl. tax - 20 discount = 180 for 2 units.
        self::assertSame(['value' => 90.0, 'currency' => 'AED'], $rows[101]['unit_price']);
        self::assertSame(['value' => 180.0, 'currency' => 'AED'], $rows[101]['row_total']);
        self::assertSame(['value' => 180.0, 'currency' => 'AED'], $rows[101]['max_refund']);

        //  A bundle's child line: its own row total and discount, as the cap reads it.
        self::assertSame(['value' => 90.0, 'currency' => 'AED'], $rows[103]['unit_price']);
        self::assertSame(['value' => 90.0, 'currency' => 'AED'], $rows[103]['max_refund']);
        self::assertSame(['label' => 'Part of bundle', 'value' => 'Starter Kit'], $rows[103]['options'][0]);

        //  One unit left of two: the cap is one unit's worth.
        self::assertSame(1.0, $rows[104]['qty_returnable']);
        self::assertSame(['value' => 25.0, 'currency' => 'AED'], $rows[104]['unit_price']);
        self::assertSame(['value' => 50.0, 'currency' => 'AED'], $rows[104]['row_total']);
        self::assertSame(['value' => 25.0, 'currency' => 'AED'], $rows[104]['max_refund']);
        self::assertSame(['R7'], $rows[104]['open_return_numbers']);
    }

    public function testAFixedPriceBundlesChildLinesAreWorthWhatTheyCarry(): void
    {
        //  Under a fixed-price bundle the price sits on the bundle line; the child lines carry 0,
        //  and so does the website's refund cap for them.
        $lines = $this->lines();
        $lines[103]['row_total_incl_tax'] = '0.0000';
        $lines[103]['discount_amount'] = '0.0000';
        $result = $this->service([$this->order()], $lines)
            ->returnableOrders(self::CUSTOMER, 1, Paging::fromArgs(null, 10, 20));

        $row = array_values(array_filter(
            $result['items'][0]['items'],
            static fn (array $r): bool => $r['order_item_id'] === 103
        ))[0];
        self::assertSame(['value' => 0.0, 'currency' => 'AED'], $row['unit_price']);
        self::assertSame(['value' => 0.0, 'currency' => 'AED'], $row['max_refund']);
    }

    public function testNoCurrencyNoPrices(): void
    {
        $order = $this->order();
        $order['order_currency_code'] = '';
        $result = $this->service([$order], $this->lines())
            ->returnableOrders(self::CUSTOMER, 1, Paging::fromArgs(null, 10, 20));

        $row = $result['items'][0]['items'][0];
        self::assertNull($row['unit_price']);
        self::assertNull($row['row_total']);
        self::assertNull($row['max_refund']);
    }

    public function testOneOrderIsLookedUpAmongTheSignedInCustomersOrdersOnly(): void
    {
        $service = $this->service([$this->order()], $this->lines(), [], single: true);

        $order = $service->returnableOrder(self::CUSTOMER, 1, ' #000000012 ');

        self::assertNotNull($order);
        self::assertSame('000000012', $order['order_number']);
        self::assertSame('Complete', $order['status_label']);
        self::assertSame([101, 103, 104], array_column($order['items'], 'order_item_id'));
        self::assertSame(['value' => 90.0, 'currency' => 'AED'], $order['items'][0]['unit_price']);
        self::assertContains(['increment_id = ?', '000000012'], $this->where);
        self::assertContains(['customer_id = ?', self::CUSTOMER], $this->where);
    }

    public function testNoOrderIsOfferedThatIsNotTheirsNotEligibleOrFullyReturned(): void
    {
        //  Not the customer's (or no such order): the same null.
        self::assertNull($this->service([], $this->lines(), [], single: true, noLines: true)
            ->returnableOrder(self::CUSTOMER, 1, '000000099'));

        //  Still pending: returns open once it is processing or complete.
        $pending = $this->order();
        $pending['state'] = 'new';
        $pending['status'] = 'pending';
        self::assertNull($this->service([$pending], $this->lines(), [], single: true, noLines: true)
            ->returnableOrder(self::CUSTOMER, 1, '000000012'));

        //  Every unit already held by a return.
        $held = [
            ['order_item_id' => 101, 'qty' => 2, 'increment_id' => 'R1', 'state' => 'open'],
            ['order_item_id' => 103, 'qty' => 1, 'increment_id' => 'R1', 'state' => 'open'],
            ['order_item_id' => 104, 'qty' => 2, 'increment_id' => 'R2', 'state' => 'closed'],
        ];
        self::assertNull($this->service([$this->order()], $this->lines(), $held, single: true)
            ->returnableOrder(self::CUSTOMER, 1, '000000012'));
    }

    public function testAnOrderNumberIsRequired(): void
    {
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Enter the order number.');
        $this->service([], [], [], single: true, noLines: true)->returnableOrder(self::CUSTOMER, 1, ' # ');
    }

    /**
     * @param array<int, array<string, mixed>> $orders sales_order rows the customer's query finds
     * @param array<int, array<string, mixed>> $lines every line of those orders
     * @param array<int, array<string, mixed>> $held ves_rma_request_item rows of those lines
     * @param bool $single hmReturnableOrder: the order comes from fetchRow, fetchAll reads only returns
     * @param bool $noLines the order's lines must not be read
     */
    private function service(
        array $orders,
        array $lines,
        array $held = [],
        bool $single = false,
        bool $noLines = false
    ): EligibilityService {
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
        if ($single) {
            $connection->method('fetchAll')->willReturn($held);
        } else {
            $connection->method('fetchAll')->willReturnOnConsecutiveCalls($orders, $held);
        }
        $connection->method('fetchRow')->willReturn($orders[0] ?? false);
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        //  refundPerUnit() and refundBase() are the real formulas; the queries and presentation are not.
        $reader = $this->getMockBuilder(OrderLineReader::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['linesOfOrders', 'present'])
            ->getMock();
        if ($noLines) {
            $reader->expects(self::never())->method('linesOfOrders');
        } else {
            //  Only the lines of the customer's own orders are read.
            $reader->method('linesOfOrders')->with([self::ORDER_ID])->willReturn($lines);
        }
        $reader->method('present')->willReturnCallback(static function (array $shown): array {
            $out = [];
            foreach ($shown as $itemId => $line) {
                $out[$itemId] = [
                    'sku' => (string) $line['sku'],
                    'name' => (string) $line['name'],
                    'options' => [],
                    'image_url' => null,
                    'seller' => null,
                ];
            }

            return $out;
        });

        $labels = $this->createMock(LabelReader::class);
        $labels->method('orderStatusLabels')->willReturn(['complete' => 'Complete']);
        $config = $this->createMock(RmaConfig::class);
        $config->method('allowPerOrder')->willReturn(true);

        return new EligibilityService($resource, new ReturnableQty(), $reader, $labels, $config);
    }

    /**
     * @return array<string, mixed>
     */
    private function order(): array
    {
        return [
            'entity_id' => (string) self::ORDER_ID,
            'increment_id' => '000000012',
            'created_at' => '2026-09-22 08:30:00',
            'status' => 'complete',
            'state' => 'complete',
            'order_currency_code' => 'AED',
        ];
    }

    /**
     * A blender (2 units), and a dynamic bundle "Starter Kit" with two child lines.
     *
     * @return array<int, array<string, mixed>>
     */
    private function lines(): array
    {
        return [
            101 => $this->line(101, null, 'simple', 'Blender', 2, '200.0000', '20.0000'),
            102 => $this->line(102, null, 'bundle', 'Starter Kit', 1, '150.0000', '10.0000'),
            103 => $this->line(103, 102, 'simple', 'Kettle', 1, '100.0000', '10.0000'),
            104 => $this->line(104, 102, 'simple', 'Mug', 2, '50.0000', '0.0000'),
        ];
    }

    /**
     * A shipped and invoiced line.
     *
     * @return array<string, mixed>
     */
    private function line(
        int $itemId,
        ?int $parentId,
        string $type,
        string $name,
        int $qty,
        string $rowTotal,
        string $discount
    ): array {
        return [
            'item_id' => (string) $itemId,
            'order_id' => (string) self::ORDER_ID,
            'parent_item_id' => $parentId === null ? null : (string) $parentId,
            'product_id' => (string) (40 + $itemId),
            'product_type' => $type,
            'sku' => 'SKU-' . $itemId,
            'name' => $name,
            'product_options' => null,
            'vendor_id' => '3',
            'qty_ordered' => $qty . '.0000',
            'qty_shipped' => $qty . '.0000',
            'qty_invoiced' => $qty . '.0000',
            'qty_refunded' => '0.0000',
            'row_total_incl_tax' => $rowTotal,
            'discount_amount' => $discount,
        ];
    }
}
