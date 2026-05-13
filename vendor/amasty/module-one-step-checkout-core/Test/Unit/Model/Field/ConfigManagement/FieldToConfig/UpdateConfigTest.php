<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\ConfigManagement\FieldToConfig;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\FieldToConfig\GetAttributeCode;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\FieldToConfig\Processor\ProcessorInterface;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\FieldToConfig\UpdateConfig;
use Magento\Config\Model\Config\Structure\Element\Field as ConfigFieldElement;
use Magento\Config\Model\Config\Structure\SearchInterface;
use Magento\Framework\DataObject;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see UpdateConfig
 * @covers UpdateConfig::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class UpdateConfigTest extends \PHPUnit\Framework\TestCase
{
    private const ATTRIBUTE_CODE = 'telephone';
    private const SOURCE_MODEL = 'Vendor\ModuleName\Model\Source';

    /**
     * @var SearchInterface|MockObject
     */
    private $systemConfigSearchMock;

    /**
     * @var GetAttributeCode|MockObject
     */
    private $getAttributeCodeMock;

    /**
     * @var Field|MockObject
     */
    private $fieldMock;

    protected function setUp(): void
    {
        $this->systemConfigSearchMock = $this->createMock(SearchInterface::class);
        $this->getAttributeCodeMock = $this->createMock(GetAttributeCode::class);
        $this->fieldMock = $this->createMock(Field::class);
    }

    public function testExecuteWithoutProcessors(): void
    {
        $subject = new UpdateConfig(
            $this->systemConfigSearchMock,
            $this->getAttributeCodeMock,
            []
        );

        $this->systemConfigSearchMock->expects($this->never())->method('getElement');
        $this->getAttributeCodeMock->expects($this->never())->method('execute');
        $subject->execute($this->fieldMock);
    }

    public function testExecuteWithInvalidProcessor(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Processor must implement '
            . 'Amasty\CheckoutCore\Model\Field\ConfigManagement\FieldToConfig\Processor\ProcessorInterface'
        );

        $invalidProcessorMock = $this->createMock(DataObject::class);
        $subject = new UpdateConfig(
            $this->systemConfigSearchMock,
            $this->getAttributeCodeMock,
            [self::SOURCE_MODEL => $invalidProcessorMock]
        );

        $this->systemConfigSearchMock->expects($this->never())->method('getElement');
        $this->getAttributeCodeMock->expects($this->never())->method('execute');
        $subject->execute($this->fieldMock);
    }

    public function testExecuteWithoutAttributeCode(): void
    {
        $this->getAttributeCodeMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->fieldMock)
            ->willReturn(null);

        $processorMock = $this->createMock(ProcessorInterface::class);
        $processorMock->expects($this->never())->method('execute');

        $subject = new UpdateConfig(
            $this->systemConfigSearchMock,
            $this->getAttributeCodeMock,
            [self::SOURCE_MODEL => $processorMock]
        );

        $this->systemConfigSearchMock->expects($this->never())->method('getElement');
        $subject->execute($this->fieldMock);
    }

    /**
     * @param ConfigFieldElement|null $configElementMock
     * @dataProvider executeWithoutSourceModelDataProvider
     */
    public function testExecuteWithoutSourceModel(?ConfigFieldElement $configElementMock): void
    {
        $processorMock = $this->createMock(ProcessorInterface::class);
        $processorMock->expects($this->never())->method('execute');

        $this->getAttributeCodeMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->fieldMock)
            ->willReturn(self::ATTRIBUTE_CODE);

        $this->systemConfigSearchMock
            ->expects($this->once())
            ->method('getElement')
            ->with('customer/address/' . self::ATTRIBUTE_CODE . '_show')
            ->willReturn($configElementMock);

        $subject = new UpdateConfig(
            $this->systemConfigSearchMock,
            $this->getAttributeCodeMock,
            [self::SOURCE_MODEL => $processorMock]
        );

        $subject->execute($this->fieldMock);
    }

    public function testExecute(): void
    {
        $configElementMock = $this->createMock(ConfigFieldElement::class);
        $configElementMock
            ->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn(['source_model' => self::SOURCE_MODEL]);

        $processorMock = $this->createMock(ProcessorInterface::class);
        $processorMock
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->fieldMock,
                'customer/address/' . self::ATTRIBUTE_CODE . '_show'
            );

        $this->getAttributeCodeMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->fieldMock)
            ->willReturn(self::ATTRIBUTE_CODE);

        $this->systemConfigSearchMock
            ->expects($this->once())
            ->method('getElement')
            ->with('customer/address/' . self::ATTRIBUTE_CODE . '_show')
            ->willReturn($configElementMock);

        $subject = new UpdateConfig(
            $this->systemConfigSearchMock,
            $this->getAttributeCodeMock,
            [self::SOURCE_MODEL => $processorMock]
        );

        $subject->execute($this->fieldMock);
    }

    public function executeWithoutSourceModelDataProvider(): array
    {
        $configElementMock = $this->createMock(ConfigFieldElement::class);
        $configElementMock
            ->expects($this->atLeastOnce())
            ->method('getData')
            ->willReturn([]); // no "source_model" key

        return [[null], [$configElementMock]];
    }
}
