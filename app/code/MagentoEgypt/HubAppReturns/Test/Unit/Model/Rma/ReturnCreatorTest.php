<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Model\Rma;

use Magento\Customer\Api\CustomerNameGenerationInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use MagentoEgypt\HubAppReturns\Model\Rma\Attachments;
use MagentoEgypt\HubAppReturns\Model\Rma\EligibilityService;
use MagentoEgypt\HubAppReturns\Model\Rma\LabelReader;
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnCreator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Vnecoms\RMA\Helper\Config as RmaConfig;
use Vnecoms\VendorsRMA\Model\Request;
use Vnecoms\VendorsRMA\Model\RequestFactory;

/**
 * hmCreateReturn files only what EligibilityService passed (the caller's order, its lines, one seller,
 * the refund cap), as the caller, in one transaction; Vnecoms' own item check still runs first. Its
 * photos, checked first, go with the first message, and a failed save removes them.
 */
final class ReturnCreatorTest extends TestCase
{
    private const CUSTOMER = 5;

    /** @var EligibilityService&MockObject */
    private EligibilityService $eligibility;

    /** @var RequestFactory&MockObject */
    private RequestFactory $requestFactory;

    /** @var AdapterInterface&MockObject */
    private AdapterInterface $connection;

    /** @var Request&MockObject */
    private Request $request;

    /** @var array<int, array<int, mixed>> setData() calls on the request */
    private array $written = [];

    /** @var Attachments&MockObject */
    private Attachments $attachments;

    protected function setUp(): void
    {
        $this->eligibility = $this->createMock(EligibilityService::class);
        $this->requestFactory = $this->createMock(RequestFactory::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->attachments = $this->createMock(Attachments::class);
        $this->attachments->method('check')->willReturn([]);
        $this->attachments->method('stage')->willReturn([]);
        $this->request = $this->createMock(Request::class);
        $this->request->method('setData')->willReturnCallback(function (...$args) {
            $this->written[] = $args;

            return $this->request;
        });
        $this->request->method('getVendorObject')->willReturn(new DataObject(['id' => 3]));
        $this->request->method('getId')->willReturn(88);
        $this->request->method('getIncrementId')->willReturn('000000088');
    }

    public function testNothingIsFiledWhenTheChecksRefuseTheReturn(): void
    {
        $this->eligibility->method('prepare')
            ->willThrowException(new GraphQlInputException(__('Items sold by different sellers need separate returns.')));
        $this->requestFactory->expects(self::never())->method('create');
        $this->connection->expects(self::never())->method('beginTransaction');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Items sold by different sellers need separate returns.');
        $this->creator()->create(self::CUSTOMER, 1, ['order_number' => '000000012']);
    }

    public function testTheRequestIsTheCheckedLinesFiledAsTheCaller(): void
    {
        $this->eligibility->expects(self::once())->method('prepare')
            ->with(self::CUSTOMER, 1, ['order_number' => '000000012'])
            ->willReturn($this->prepared());
        $this->requestFactory->method('create')->willReturn($this->request);
        $this->request->method('validateItems')->willReturn(true);
        $this->request->method('validate')->willReturn(true);
        $this->request->expects(self::once())->method('save');
        $this->request->expects(self::once())->method('saveItemsObject')
            ->with([['item_id' => 101, 'item_qty' => 2]]);
        $this->request->expects(self::once())->method('saveAmountRefundObject')->with('custom_amount', 180.0);
        $this->connection->expects(self::once())->method('beginTransaction');
        $this->connection->expects(self::once())->method('commit');
        $this->connection->expects(self::never())->method('rollBack');

        self::assertSame(88, $this->creator()->create(self::CUSTOMER, 1, ['order_number' => '000000012']));

        $data = $this->written[0][0];
        self::assertSame(self::CUSTOMER, $data['customer_id']);
        self::assertSame('000000012', $data['order_incremental_id']);
        self::assertSame('mona@example.com', $data['customer_email']);
        self::assertSame([['item_id' => 101, 'item_qty' => 2]], $data['order_item_id']);
        self::assertSame('custom_amount', $data['refund_amount_type']);
        self::assertSame(180.0, $data['refund_custom_amount']);
        //  The request's own flags: unread for admin and seller, read for the customer who filed it.
        self::assertContains(['is_admin_read', 0], $this->written);
        self::assertContains(['is_vendor_read', 0], $this->written);
        self::assertContains(['is_customer_read', 1], $this->written);
    }

    public function testVnecomsOwnItemCheckStillRunsAndFilesNothingWhenItFails(): void
    {
        $this->eligibility->method('prepare')->willReturn($this->prepared());
        $this->requestFactory->method('create')->willReturn($this->request);
        $this->request->method('validateItems')->willReturn(['You can not request RMA for items of different vendors.']);
        $this->request->expects(self::never())->method('save');
        $this->connection->expects(self::never())->method('beginTransaction');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('You can not request RMA for items of different vendors.');
        $this->creator()->create(self::CUSTOMER, 1, ['order_number' => '000000012']);
    }

    public function testAFailedWriteLeavesNoHalfFiledReturn(): void
    {
        $this->eligibility->method('prepare')->willReturn($this->prepared());
        $this->requestFactory->method('create')->willReturn($this->request);
        $this->request->method('validateItems')->willReturn(true);
        $this->request->method('validate')->willReturn(true);
        $this->request->method('saveItemsObject')->willThrowException(new \RuntimeException('deadlock'));
        $this->connection->expects(self::once())->method('beginTransaction');
        $this->connection->expects(self::once())->method('rollBack');
        $this->connection->expects(self::never())->method('commit');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('We couldn\'t file the return. Please try again.');
        $this->creator()->create(self::CUSTOMER, 1, ['order_number' => '000000012']);
    }

    public function testPhotosGoWithTheFirstMessageAndAFailedWriteRemovesThem(): void
    {
        $photo = ['name' => 'crack.png', 'extension' => 'png', 'content' => 'PNG'];
        $input = ['order_number' => '000000012', 'attachments' => [['name' => 'crack.png']]];
        $this->attachments = $this->createMock(Attachments::class);
        $this->attachments->expects(self::once())->method('check')->with([['name' => 'crack.png']])->willReturn([$photo]);
        $this->attachments->expects(self::once())->method('stage')->with([$photo])->willReturn(['crack_x1.png']);
        $this->eligibility->method('prepare')->willReturn($this->prepared());
        $this->requestFactory->method('create')->willReturn($this->request);
        $this->request->method('validateItems')->willReturn(true);
        $this->request->method('validate')->willReturn(true);
        //  Vnecoms moves the staged file when it saves the message.
        $this->request->expects(self::once())->method('saveMessageObject')
            ->with(self::callback(static fn (array $message): bool => $message['attachment'] === 'crack_x1.png'));
        $this->request->method('saveItemsObject')->willThrowException(new \RuntimeException('deadlock'));
        $this->attachments->expects(self::once())->method('discard')->with(['crack_x1.png']);
        $this->attachments->expects(self::never())->method('sweep');

        $this->expectException(GraphQlInputException::class);
        $this->creator()->create(self::CUSTOMER, 1, $input);
    }

    public function testAPhotoTheRulesRefuseFilesNothing(): void
    {
        $this->attachments = $this->createMock(Attachments::class);
        $this->attachments->method('check')
            ->willThrowException(new GraphQlInputException(__('Attach at most %1 files.', 5)));
        $this->attachments->expects(self::never())->method('stage');
        $this->eligibility->method('prepare')->willReturn($this->prepared());
        $this->requestFactory->expects(self::never())->method('create');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Attach at most 5 files.');
        $this->creator()->create(self::CUSTOMER, 1, ['order_number' => '000000012', 'attachments' => []]);
    }

    private function creator(): ReturnCreator
    {
        $labels = $this->createMock(LabelReader::class);
        $labels->method('statusIdByCode')->willReturn(1);
        $config = $this->createMock(RmaConfig::class);
        $config->method('getClientIP')->willReturn('203.0.113.9');
        $config->method('converText')->willReturnArgument(0);
        $config->method('contactsName')->willReturn('');
        $customers = $this->createMock(CustomerRepositoryInterface::class);
        $customers->method('getById')->with(self::CUSTOMER)->willReturn($this->createMock(CustomerInterface::class));
        $names = $this->createMock(CustomerNameGenerationInterface::class);
        $names->method('getCustomerName')->willReturn('Mona Ali');
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($this->connection);
        $remote = $this->createMock(RemoteAddress::class);
        $remote->method('getRemoteAddress')->willReturn('203.0.113.9');

        return new ReturnCreator(
            $this->eligibility,
            $this->requestFactory,
            $labels,
            $config,
            $customers,
            $names,
            $resource,
            $this->createMock(EventManager::class),
            $this->createMock(RequestInterface::class),
            $remote,
            $this->createMock(LoggerInterface::class),
            $this->attachments
        );
    }

    /**
     * What EligibilityService::prepare returns for the caller's order 000000012, 2 of line 101.
     *
     * @return array<string, mixed>
     */
    private function prepared(): array
    {
        return [
            'order' => [
                'entity_id' => '12',
                'increment_id' => '000000012',
                'status' => 'complete',
                'state' => 'complete',
                'customer_email' => 'mona@example.com',
                'order_currency_code' => 'AED',
                'store_id' => '1',
            ],
            'items' => [['item_id' => 101, 'item_qty' => 2]],
            'type' => 'refund',
            'reason' => 1,
            'other_reason' => '',
            'package_opened' => 1,
            'comment' => 'It arrived broken.',
            'refund_amount_type' => 'custom_amount',
            'refund_custom_amount' => 180.0,
            'tracking_code' => '',
        ];
    }
}
