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
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnReader;
use PHPUnit\Framework\TestCase;
use Vnecoms\VendorsRMA\Model\Request;
use Vnecoms\VendorsRMA\Model\RequestFactory;

/**
 * hmReturn: what the website's page offers (reply, Cancel, Escalate RMA) as can_reply, can_cancel and
 * can_escalate; the customer's escalation as its RMA Escalate tab shows it; the messages' files.
 */
final class ReturnDetailTest extends TestCase
{
    private const CUSTOMER = 5;

    private const STATUSES = [
        1 => ['code' => 'pending', 'label' => 'Open'],
        3 => ['code' => 'package_sent', 'label' => 'Package Sent'],
        6 => ['code' => 'canceled', 'label' => 'Canceled'],
        7 => ['code' => 'resolved', 'label' => 'Resolved'],
        9 => ['code' => 'awaiting', 'label' => 'Escalated'],
    ];

    public function testAPendingReturnTakesRepliesCancelAndEscalation(): void
    {
        $rma = $this->detail(1, 'open', [], [
            [
                'message_id' => '1',
                'message' => '<p>It arrived cracked.</p>',
                'attachment' => 'crack_ab12cd34ef.png,box.jpg',
                'type' => 'CUSTOMER REPLY',
                'from' => 'Mona Ali',
                'created_at' => '2026-09-24 14:02:00',
            ],
        ]);

        self::assertTrue($rma['can_reply']);
        self::assertTrue($rma['can_cancel']);
        self::assertTrue($rma['can_escalate']);
        self::assertNull($rma['escalation']);
        self::assertSame(
            [
                ['name' => 'crack_ab12cd34ef.png', 'url' => 'https://hub.test/media/rma/request/crack_ab12cd34ef.png'],
                ['name' => 'box.jpg', 'url' => 'https://hub.test/media/rma/request/box.jpg'],
            ],
            $rma['messages'][0]['attachments']
        );
        self::assertSame(
            array_column($rma['messages'][0]['attachments'], 'url'),
            $rma['messages'][0]['attachment_urls']
        );
    }

    public function testOnTheWayItCanNoLongerBeCancelled(): void
    {
        $rma = $this->detail(3, 'open');

        self::assertTrue($rma['can_reply']);
        self::assertFalse($rma['can_cancel']);
        self::assertTrue($rma['can_escalate']);
    }

    public function testAnEscalatedReturnShowsTheCustomersEscalation(): void
    {
        $rma = $this->detail(9, 'awaiting', [
            [
                'escalate_id' => '4',
                'message' => '<p>The seller stopped answering.</p>',
                'attachment' => 'crack_1.png',
                'type' => 'CUSTOMER REPLY',
                'created_at' => '2026-09-29 08:00:00',
            ],
            [
                'escalate_id' => '5',
                'message' => '<p>Seller evidence.</p>',
                'attachment' => '',
                'type' => 'VENDOR REPLY',
                'created_at' => '2026-09-29 09:00:00',
            ],
        ]);

        self::assertSame('OPEN', $rma['state']);
        self::assertTrue($rma['can_reply']);
        self::assertFalse($rma['can_cancel']);
        self::assertFalse($rma['can_escalate']);
        self::assertSame([
            'body_html' => '<p>The seller stopped answering.</p>',
            'body_text' => 'The seller stopped answering.',
            'attachments' => [['name' => 'crack_1.png', 'url' => 'https://hub.test/media/rma/request/crack_1.png']],
            'created_at' => '2026-09-29T08:00:00Z',
        ], $rma['escalation']);
    }

    public function testACancelledReturnOffersNothing(): void
    {
        $rma = $this->detail(6, 'canceled');

        self::assertFalse($rma['can_reply']);
        self::assertFalse($rma['can_cancel']);
        self::assertFalse($rma['can_escalate']);
    }

    public function testAResolvedReturnCanStillBeEscalated(): void
    {
        $rma = $this->detail(7, 'closed');

        self::assertSame('CLOSED', $rma['state']);
        self::assertFalse($rma['can_reply']);
        self::assertFalse($rma['can_cancel']);
        self::assertTrue($rma['can_escalate']);
    }

    /**
     * HmReturn of the customer's return 31 with this status, state, escalations and messages.
     *
     * @param array<int, array<string, mixed>> $escalations ves_rma_request_escalate rows
     * @param array<int, array<string, mixed>> $messages ves_rma_request_message rows
     * @return array<string, mixed>
     */
    private function detail(int $status, string $state, array $escalations = [], array $messages = []): array
    {
        $data = [
            'status' => $status,
            'state' => $state,
            'is_customer_read' => 1,
            'increment_id' => 'R-000031',
            'order_incremental_id' => '000000150',
            'created_at' => '2026-09-24 14:02:00',
            'updated_at' => '2026-09-25 05:15:00',
            'type' => 'replace',
            'vendor_id' => 12,
            'customer_name' => 'Mona Ali',
        ];
        $request = $this->createMock(Request::class);
        $request->method('getId')->willReturn(31);
        $request->method('getCustomerId')->willReturn(self::CUSTOMER);
        $request->method('getData')->willReturnCallback(static fn (string $key = '') => $data[$key] ?? null);
        $factory = $this->createMock(RequestFactory::class);
        $factory->method('create')->willReturn($request);

        $select = $this->createMock(Select::class);
        foreach (['from', 'where', 'order', 'limit', 'join'] as $method) {
            $select->method($method)->willReturnSelf();
        }
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        //  The return's lines, its escalations, its history, its messages.
        $connection->method('fetchAll')->willReturnOnConsecutiveCalls([], $escalations, [], $messages);
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        $labels = $this->createMock(LabelReader::class);
        $labels->method('statuses')->willReturn(self::STATUSES);
        $labels->method('reasons')->willReturn([]);
        $lines = $this->createMock(OrderLineReader::class);
        $lines->method('linesById')->willReturn([]);
        $lines->method('present')->willReturn([]);
        $lines->method('sellerSummaries')->willReturn([]);
        $media = $this->createMock(MediaUrlInterface::class);
        $media->method('media')->willReturnCallback(static fn (string $path): string => 'https://hub.test/media/' . $path);

        $rma = (new ReturnReader($resource, $factory, $labels, $lines, $media))->detail(self::CUSTOMER, 31, 1);
        self::assertNotNull($rma);

        return $rma;
    }
}
