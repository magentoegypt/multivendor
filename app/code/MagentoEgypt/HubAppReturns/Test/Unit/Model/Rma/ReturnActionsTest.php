<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Model\Rma;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use MagentoEgypt\HubAppReturns\Model\Rma\Attachments;
use MagentoEgypt\HubAppReturns\Model\Rma\LabelReader;
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnActions;
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnReader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Vnecoms\RMA\Helper\Config as RmaConfig;
use Vnecoms\VendorsRMA\Model\Request;

/**
 * hmCancelReturn and hmEscalateReturn: the website's Cancel and Escalate on the customer's own return,
 * only where its page offers them, dated now, in one transaction, the seller notified after.
 */
final class ReturnActionsTest extends TestCase
{
    private const CUSTOMER = 5;
    private const NOW = '2026-09-30 10:00:00';

    /** Status ids of a standard install. */
    private const STATUSES = [
        1 => ['code' => 'pending', 'label' => 'Open'],
        2 => ['code' => 'approval', 'label' => 'Request Accepted'],
        3 => ['code' => 'package_sent', 'label' => 'Package Sent'],
        6 => ['code' => 'canceled', 'label' => 'Canceled'],
        7 => ['code' => 'resolved', 'label' => 'Resolved'],
        8 => ['code' => 'being', 'label' => 'Being Reviewed By Admin'],
        9 => ['code' => 'awaiting', 'label' => 'Escalated'],
    ];

    /** @var ReturnReader&MockObject */
    private ReturnReader $reader;

    /** @var Attachments&MockObject */
    private Attachments $attachments;

    /** @var AdapterInterface&MockObject */
    private AdapterInterface $connection;

    /** @var EventManager&MockObject */
    private EventManager $events;

    /** @var array<string, mixed> the return's data, as loaded and as set */
    private array $data = [];

    /** @var string[] events dispatched */
    private array $dispatched = [];

    protected function setUp(): void
    {
        $this->reader = $this->createMock(ReturnReader::class);
        $this->attachments = $this->createMock(Attachments::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->events = $this->createMock(EventManager::class);
        $this->events->method('dispatch')->willReturnCallback(function (string $name): void {
            $this->dispatched[] = $name;
        });
    }

    public function testAPendingReturnIsCancelledAsTheCustomerNow(): void
    {
        $request = $this->request(1, 'open');
        $request->expects(self::once())->method('save');
        $request->expects(self::once())->method('saveStatusHistoryObject')->with(false);
        $this->connection->expects(self::once())->method('commit');

        $this->actions()->cancel(self::CUSTOMER, 31);

        self::assertSame(6, $this->data['status']);
        self::assertSame(self::NOW, $this->data['updated_at']);
        self::assertSame(0, $this->data['is_admin_read']);
        self::assertSame(0, $this->data['is_vendor_read']);
        self::assertSame(1, $this->data['is_customer_read']);
        self::assertSame(['rma_request_prepare_save', 'vnecoms_vendors_push_notification'], $this->dispatched);
    }

    public function testAnAcceptedReturnCanStillBeCancelled(): void
    {
        $this->request(2, 'open')->expects(self::once())->method('save');

        $this->actions()->cancel(self::CUSTOMER, 31);

        self::assertSame(6, $this->data['status']);
    }

    public function testNoCancelOnceThePackageIsOnItsWay(): void
    {
        $request = $this->request(3, 'open');
        $request->expects(self::never())->method('save');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('This return can\'t be cancelled any more.');
        $this->actions()->cancel(self::CUSTOMER, 31);
    }

    public function testSomeoneElsesReturnDoesNotExist(): void
    {
        $this->reader->method('load')->willReturn(null);

        $this->expectException(GraphQlNoSuchEntityException::class);
        $this->expectExceptionMessage('This return doesn\'t exist.');
        $this->actions()->cancel(self::CUSTOMER, 99);
    }

    public function testAFailedCancelChangesNothing(): void
    {
        $request = $this->request(1, 'open');
        $request->method('saveStatusHistoryObject')->willThrowException(new \RuntimeException('deadlock'));
        $this->connection->expects(self::once())->method('rollBack');
        $this->connection->expects(self::never())->method('commit');

        try {
            $this->actions()->cancel(self::CUSTOMER, 31);
            self::fail('A failed cancel was not reported.');
        } catch (GraphQlInputException $e) {
            self::assertSame('We couldn\'t cancel the return. Please try again.', $e->getMessage());
        }
        self::assertNotContains('vnecoms_vendors_push_notification', $this->dispatched);
    }

    public function testAnEscalationGoesToHubMarketWithItsPhotos(): void
    {
        $request = $this->request(1, 'open');
        $photo = ['name' => 'crack.png', 'extension' => 'png', 'content' => 'PNG'];
        $this->attachments->expects(self::once())->method('check')->with([['name' => 'crack.png']])->willReturn([$photo]);
        $this->attachments->expects(self::once())->method('stage')->with([$photo])->willReturn(['crack_x1.png']);
        $this->attachments->expects(self::once())->method('sweep')->with(['crack_x1.png']);
        $request->expects(self::once())->method('saveEscalateObject')->with([
            'message' => '<p>The seller stopped answering &amp; the item is broken.</p>',
            'attachment' => 'crack_x1.png',
            'type' => 'CUSTOMER REPLY',
        ]);
        $request->expects(self::once())->method('saveStatusHistoryObject')->with(false);
        $this->connection->expects(self::once())->method('commit');

        $this->actions()->escalate(
            self::CUSTOMER,
            31,
            ' The seller stopped answering & the item is broken. ',
            [['name' => 'crack.png']]
        );

        //  Escalated (awaiting), dated now.
        self::assertSame(9, $this->data['status']);
        self::assertSame(self::NOW, $this->data['updated_at']);
        self::assertSame(
            ['rma_request_prepare_save', 'rma_request_escalate_after', 'vnecoms_vendors_push_notification'],
            $this->dispatched
        );
    }

    public function testAnEscalatedReturnMovesOnToBeingReviewed(): void
    {
        //  Set to Escalated by staff without an escalation: the customer's takes it on.
        $this->request(9, 'awaiting');
        $this->attachments->method('check')->willReturn([]);
        $this->attachments->method('stage')->willReturn([]);

        $this->actions()->escalate(self::CUSTOMER, 31, 'Please look at this.');

        self::assertSame(8, $this->data['status']);
    }

    public function testAReturnIsEscalatedOnlyOnce(): void
    {
        $request = $this->request(9, 'awaiting', [['escalate_id' => 1, 'type' => 'CUSTOMER REPLY']]);
        $request->expects(self::never())->method('save');
        $this->attachments->expects(self::never())->method('stage');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('This return has already been escalated to Hub Market.');
        $this->actions()->escalate(self::CUSTOMER, 31, 'Again?');
    }

    public function testACancelledReturnIsNotEscalated(): void
    {
        $request = $this->request(6, 'canceled');
        $request->expects(self::never())->method('save');
        $this->attachments->expects(self::never())->method('stage');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('A cancelled return can\'t be escalated.');
        $this->actions()->escalate(self::CUSTOMER, 31, 'Why?');
    }

    public function testAnEscalationNeedsAMessage(): void
    {
        $this->reader->expects(self::never())->method('load');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Tell Hub Market what went wrong.');
        $this->actions()->escalate(self::CUSTOMER, 31, "  \n ");
    }

    public function testAFailedEscalationRemovesItsPhotos(): void
    {
        $request = $this->request(1, 'open');
        $this->attachments->method('check')->willReturn([['name' => 'a.png', 'extension' => 'png', 'content' => 'A']]);
        $this->attachments->method('stage')->willReturn(['a_x1.png']);
        $request->method('saveEscalateObject')->willThrowException(new \RuntimeException('deadlock'));
        $this->connection->expects(self::once())->method('rollBack');
        $this->attachments->expects(self::once())->method('discard')->with(['a_x1.png']);
        $this->attachments->expects(self::never())->method('sweep');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('We couldn\'t escalate the return. Please try again.');
        $this->actions()->escalate(self::CUSTOMER, 31, 'Broken.', [['name' => 'a.png']]);
    }

    /**
     * The customer's return 31 with this status and state, and these escalation rows.
     *
     * @param array<int, array<string, mixed>> $escalations
     * @return Request&MockObject
     */
    private function request(int $status, string $state, array $escalations = []): Request
    {
        $this->data = ['status' => $status, 'state' => $state];
        $request = $this->createMock(Request::class);
        $request->method('getData')->willReturnCallback(fn (string $key = '') => $this->data[$key] ?? null);
        $request->method('setData')->willReturnCallback(function (string $key, $value) use ($request) {
            $this->data[$key] = $value;

            return $request;
        });
        $request->method('getVendorObject')->willReturn(new DataObject(['id' => 3]));
        $request->method('getId')->willReturn(31);
        $request->method('getIncrementId')->willReturn('R-000031');
        $request->method('getStatusTitle')->willReturn('Canceled');
        $this->reader->method('load')->with(self::CUSTOMER, 31)->willReturn($request);
        $this->reader->method('escalations')->with(31)->willReturn($escalations);

        return $request;
    }

    private function actions(): ReturnActions
    {
        $labels = $this->createMock(LabelReader::class);
        $labels->method('statuses')->willReturn(self::STATUSES);
        $labels->method('statusIdByCode')->willReturnCallback(static function (string $code, int $fallback): int {
            foreach (self::STATUSES as $id => $status) {
                if ($status['code'] === $code) {
                    return $id;
                }
            }

            return $fallback;
        });
        $config = $this->createMock(RmaConfig::class);
        $config->method('converText')->willReturnArgument(0);
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($this->connection);
        $dateTime = $this->createMock(DateTime::class);
        $dateTime->method('gmtDate')->willReturn(self::NOW);

        return new ReturnActions(
            $this->reader,
            $labels,
            $config,
            $this->attachments,
            $resource,
            $this->events,
            $this->createMock(RequestInterface::class),
            $dateTime,
            $this->createMock(LoggerInterface::class)
        );
    }
}
