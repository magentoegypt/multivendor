<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\Form;

use Amasty\CheckoutCore\Cache\InvalidateCheckoutCache;
use Amasty\CheckoutCore\Model\Field\Form\Processor\ProcessorInterface;
use Amasty\CheckoutCore\Model\Field\Form\SaveHandler;
use Amasty\CheckoutCore\Model\ResourceModel\Field as FieldResource;
use Magento\Framework\DataObject;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see SaveHandler
 * @covers SaveHandler::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class SaveHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const STORE_ID = 1;

    /**
     * @var FieldResource|MockObject
     */
    private $fieldResourceMock;

    /**
     * @var InvalidateCheckoutCache|MockObject
     */
    private $invalidateCheckoutCacheMock;

    protected function setUp(): void
    {
        $this->fieldResourceMock = $this->createMock(FieldResource::class);
        $this->invalidateCheckoutCacheMock = $this->createMock(InvalidateCheckoutCache::class);
    }

    public function testExecuteWithNoFields(): void
    {
        $this->fieldResourceMock->expects($this->never())->method('beginTransaction');
        $this->fieldResourceMock->expects($this->never())->method('rollBack');
        $this->fieldResourceMock->expects($this->never())->method('getConnection');
        $this->invalidateCheckoutCacheMock->expects($this->never())->method('execute');

        $subject = new SaveHandler(
            $this->fieldResourceMock,
            $this->invalidateCheckoutCacheMock,
            [$this->createMock(ProcessorInterface::class)]
        );

        $subject->execute([], self::STORE_ID);
    }

    /**
     * @param array $fields
     * @dataProvider fieldsDataProvider
     */
    public function testExecuteWithNoProcessors(array $fields): void
    {
        $this->fieldResourceMock->expects($this->never())->method('beginTransaction');
        $this->fieldResourceMock->expects($this->never())->method('rollBack');
        $this->fieldResourceMock->expects($this->never())->method('getConnection');
        $this->invalidateCheckoutCacheMock->expects($this->never())->method('execute');

        $subject = new SaveHandler(
            $this->fieldResourceMock,
            $this->invalidateCheckoutCacheMock,
            []
        );

        $subject->execute($fields, self::STORE_ID);
    }

    /**
     * @param array $fields
     * @dataProvider fieldsDataProvider
     */
    public function testExecuteWithInvalidProcessor(array $fields): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Processor must implement Amasty\CheckoutCore\Model\Field\Form\Processor\ProcessorInterface'
        );

        $this->fieldResourceMock->expects($this->never())->method('beginTransaction');
        $this->fieldResourceMock->expects($this->never())->method('rollBack');
        $this->fieldResourceMock->expects($this->never())->method('getConnection');
        $this->invalidateCheckoutCacheMock->expects($this->never())->method('execute');

        $subject = new SaveHandler(
            $this->fieldResourceMock,
            $this->invalidateCheckoutCacheMock,
            [$this->createMock(DataObject::class)]
        );

        $subject->execute($fields, self::STORE_ID);
    }

    /**
     * @param array $fields
     * @dataProvider fieldsDataProvider
     * @SuppressWarnings(PHPMD.MissingImport)
     */
    public function testExecuteWithExceptionInsideTransaction(array $fields): void
    {
        $exceptionMessage = 'test';
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($exceptionMessage);

        $processorMock = $this->createMock(ProcessorInterface::class);
        $processorMock
            ->expects($this->once())
            ->method('process')
            ->with($fields, self::STORE_ID)
            ->willThrowException(new \Exception($exceptionMessage));

        $this->fieldResourceMock->expects($this->once())->method('beginTransaction');
        $this->fieldResourceMock->expects($this->once())->method('rollBack');
        $this->fieldResourceMock->expects($this->never())->method('getConnection');
        $this->invalidateCheckoutCacheMock->expects($this->never())->method('execute');

        $subject = new SaveHandler(
            $this->fieldResourceMock,
            $this->invalidateCheckoutCacheMock,
            [$processorMock]
        );

        $subject->execute($fields, self::STORE_ID);
    }

    public function fieldsDataProvider(): array
    {
        return [
            [
                [1 => ['attribute_id' => 1]]
            ]
        ];
    }
}
