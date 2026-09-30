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
use MagentoEgypt\HubAppReturns\Model\Rma\Attachments;
use MagentoEgypt\HubAppReturns\Model\Rma\MessagePoster;
use MagentoEgypt\HubAppReturns\Model\Rma\ReturnReader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Vnecoms\RMA\Helper\Config as RmaConfig;
use Vnecoms\VendorsRMA\Model\Request;

/**
 * hmAddReturnMessage: a reply on the customer's own open return, its photos staged for Vnecoms' save
 * to move, and removed again when the save fails.
 */
final class MessagePosterTest extends TestCase
{
    private const CUSTOMER = 5;

    /** @var ReturnReader&MockObject */
    private ReturnReader $reader;

    /** @var Attachments&MockObject */
    private Attachments $attachments;

    /** @var AdapterInterface&MockObject */
    private AdapterInterface $connection;

    /** @var Request&MockObject */
    private Request $request;

    protected function setUp(): void
    {
        $this->reader = $this->createMock(ReturnReader::class);
        $this->attachments = $this->createMock(Attachments::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->request = $this->createMock(Request::class);
        $this->request->method('getVendorObject')->willReturn(new DataObject(['id' => 3, 'name' => 'loly store']));
        $this->request->method('getData')->willReturnMap([['customer_name', null, 'Mona Ali']]);
        $this->request->method('getId')->willReturn(31);
        $this->request->method('getIncrementId')->willReturn('R-000031');
    }

    public function testPhotosGoWithTheReply(): void
    {
        $photo = ['name' => 'crack.png', 'extension' => 'png', 'content' => 'PNG'];
        $this->reader->method('load')->with(self::CUSTOMER, 31)->willReturn($this->request);
        $this->request->method('getState')->willReturn('open');
        $this->attachments->expects(self::once())->method('check')->with([['name' => 'crack.png']])->willReturn([$photo]);
        $this->attachments->expects(self::once())->method('stage')->with([$photo])->willReturn(['crack_x1.png']);
        $this->request->expects(self::once())->method('saveMessageObject')->with(self::callback(
            static fn (array $message): bool => $message['attachment'] === 'crack_x1.png'
                && $message['message'] === '<p>Here it is.</p>'
                && $message['to'] === 'loly store'
        ));
        $this->connection->expects(self::once())->method('commit');
        $this->attachments->expects(self::once())->method('sweep')->with(['crack_x1.png']);
        $this->attachments->expects(self::never())->method('discard');

        $this->poster()->post(self::CUSTOMER, 31, ' Here it is. ', [['name' => 'crack.png']]);
    }

    public function testAReplyWithoutPhotosCarriesNone(): void
    {
        $this->reader->method('load')->willReturn($this->request);
        $this->request->method('getState')->willReturn('awaiting');
        $this->attachments->method('check')->with(null)->willReturn([]);
        $this->attachments->method('stage')->willReturn([]);
        $this->request->expects(self::once())->method('saveMessageObject')
            ->with(self::callback(static fn (array $message): bool => $message['attachment'] === null));

        $this->poster()->post(self::CUSTOMER, 31, 'Any news?');
    }

    public function testAClosedReturnTakesNoReplyAndStagesNothing(): void
    {
        $this->reader->method('load')->willReturn($this->request);
        $this->request->method('getState')->willReturn('closed');
        $this->attachments->expects(self::never())->method('stage');
        $this->request->expects(self::never())->method('save');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('This return is closed, so it can\'t take new messages.');
        $this->poster()->post(self::CUSTOMER, 31, 'Hello?', [['name' => 'a.png']]);
    }

    public function testSomeoneElsesReturnDoesNotExist(): void
    {
        $this->reader->method('load')->willReturn(null);
        $this->attachments->expects(self::never())->method('stage');

        $this->expectException(GraphQlNoSuchEntityException::class);
        $this->poster()->post(self::CUSTOMER, 99, 'Hello?');
    }

    public function testAFailedSaveRemovesThePhotos(): void
    {
        $this->reader->method('load')->willReturn($this->request);
        $this->request->method('getState')->willReturn('open');
        $this->attachments->method('check')->willReturn([['name' => 'a.png', 'extension' => 'png', 'content' => 'A']]);
        $this->attachments->method('stage')->willReturn(['a_x1.png']);
        $this->request->method('saveMessageObject')->willThrowException(new \RuntimeException('deadlock'));
        $this->connection->expects(self::once())->method('rollBack');
        $this->attachments->expects(self::once())->method('discard')->with(['a_x1.png']);
        $this->attachments->expects(self::never())->method('sweep');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('We couldn\'t send the message. Please try again.');
        $this->poster()->post(self::CUSTOMER, 31, 'Photo attached.', [['name' => 'a.png']]);
    }

    private function poster(): MessagePoster
    {
        $config = $this->createMock(RmaConfig::class);
        $config->method('converText')->willReturnArgument(0);
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($this->connection);

        return new MessagePoster(
            $this->reader,
            $config,
            $resource,
            $this->createMock(EventManager::class),
            $this->createMock(RequestInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->attachments
        );
    }
}
