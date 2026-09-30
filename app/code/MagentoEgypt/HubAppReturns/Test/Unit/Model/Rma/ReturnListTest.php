<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Model\Rma;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use MagentoEgypt\HubApp\Api\MediaUrlInterface;
use MagentoEgypt\HubAppReturns\Model\Rma\LabelReader;
use MagentoEgypt\HubAppReturns\Model\Rma\OrderLineReader;
use MagentoEgypt\HubAppReturns\Model\Rma\Paging;
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnReader;
use PHPUnit\Framework\TestCase;
use Vnecoms\VendorsRMA\Model\RequestFactory;

/**
 * hmReturns rows: the status code (the app colours a resolved return apart from a cancelled one), the
 * refund amount of a refund in its order's currency, and the first line with its thumbnail, read for
 * the whole page at once.
 */
final class ReturnListTest extends TestCase
{
    private const CUSTOMER = 5;

    /** @var array<int, array{0: string, 1: mixed}> where() calls of the queries */
    private array $where = [];

    public function testEachRowCarriesItsStatusCodeRefundAndFirstLine(): void
    {
        $page = $this->reader()->page(self::CUSTOMER, 1, 1, Paging::fromArgs(null, 20, 50));

        self::assertSame(3, $page['total']);
        $rows = [];
        foreach ($page['items'] as $row) {
            $rows[$row['number']] = $row;
        }

        $open = $rows['R-000031'];
        self::assertSame('pending', $open['status_code']);
        self::assertSame('OPEN', $open['state']);
        self::assertSame(['value' => 43.0, 'currency' => 'AED'], $open['refund_amount']);
        self::assertSame(2, $open['item_count']);
        self::assertSame(
            ['name' => 'Short Square-Neck T-Shirt', 'thumbnail' => 'https://hub.test/media/t.jpg'],
            $open['first_item']
        );

        //  Its first line's order line is gone: no picture, the rest as usual.
        $resolved = $rows['R-000027'];
        self::assertSame('resolved', $resolved['status_code']);
        self::assertSame('CLOSED', $resolved['state']);
        self::assertSame(['value' => 29.0, 'currency' => 'SAR'], $resolved['refund_amount']);
        self::assertNull($resolved['first_item']);

        //  An exchange has no refund amount, whatever is stored.
        $canceled = $rows['R-000019'];
        self::assertSame('canceled', $canceled['status_code']);
        self::assertSame('CANCELED', $canceled['state']);
        self::assertSame('REPLACE', $canceled['type']);
        self::assertNull($canceled['refund_amount']);
        self::assertSame(['name' => 'Polo Shirt', 'thumbnail' => null], $canceled['first_item']);
        self::assertSame(1, $canceled['item_count']);

        //  The currencies are read from the customer's own orders.
        self::assertContains(['customer_id = ?', self::CUSTOMER], $this->where);
        self::assertContains(['increment_id IN (?)', ['000000150', '000000148']], $this->where);
    }

    private function reader(): ReturnReader
    {
        $select = $this->createMock(Select::class);
        foreach (['from', 'order', 'limit', 'group'] as $method) {
            $select->method($method)->willReturnSelf();
        }
        $select->method('where')->willReturnCallback(function (string $condition, $value = null) use ($select) {
            $this->where[] = [$condition, $value];

            return $select;
        });
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchOne')->willReturn('3');
        $connection->method('fetchAll')->willReturnOnConsecutiveCalls(
            [
                $this->row(31, 'R-000031', '000000150', 'refund', 1, 'open', '43.0000'),
                $this->row(27, 'R-000027', '000000148', 'refund', 7, 'closed', '29.0000'),
                $this->row(19, 'R-000019', '000000150', 'replace', 6, 'canceled', '10.0000'),
            ],
            //  ves_rma_request_item of the page, by request then line.
            [
                ['request_id' => '19', 'order_item_id' => '480'],
                ['request_id' => '27', 'order_item_id' => '502'],
                ['request_id' => '31', 'order_item_id' => '501'],
                ['request_id' => '31', 'order_item_id' => '503'],
            ]
        );
        $connection->method('fetchPairs')->willReturn(['000000150' => 'AED', '000000148' => 'SAR']);
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        $labels = $this->createMock(LabelReader::class);
        $labels->method('statuses')->willReturn([
            1 => ['code' => 'pending', 'label' => 'Open'],
            6 => ['code' => 'canceled', 'label' => 'Canceled'],
            7 => ['code' => 'resolved', 'label' => 'Resolved'],
        ]);

        $lines = $this->createMock(OrderLineReader::class);
        //  Only the first line of each return is read; line 502 no longer exists.
        $lines->expects(self::once())->method('linesById')->with([480, 502, 501])->willReturn([
            480 => ['item_id' => '480', 'name' => 'Polo Shirt'],
            501 => ['item_id' => '501', 'name' => 'Short Square-Neck T-Shirt'],
        ]);
        $lines->method('present')->willReturn([
            480 => ['name' => 'Polo Shirt', 'image_url' => null],
            501 => ['name' => 'Short Square-Neck T-Shirt', 'image_url' => 'https://hub.test/media/t.jpg'],
        ]);
        $lines->method('sellerSummaries')->willReturn([]);

        return new ReturnReader(
            $resource,
            $this->createMock(RequestFactory::class),
            $labels,
            $lines,
            $this->createMock(MediaUrlInterface::class)
        );
    }

    /**
     * @return array<string, mixed> ves_rma_request_entity columns the list reads
     */
    private function row(
        int $id,
        string $number,
        string $order,
        string $type,
        int $status,
        string $state,
        ?string $refund
    ): array {
        return [
            'entity_id' => (string) $id,
            'increment_id' => $number,
            'order_incremental_id' => $order,
            'created_at' => '2026-09-24 14:02:00',
            'updated_at' => '2026-09-25 05:15:00',
            'state' => $state,
            'status' => (string) $status,
            'type' => $type,
            'vendor_id' => '12',
            'is_customer_read' => '1',
            'refund_amount' => $refund,
        ];
    }
}
