<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\ConfigManagement\ConfigToField;

use Amasty\CheckoutCore\Cache\InvalidateCheckoutCache;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToField\GetAttributeCode;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToField\ProcessConfigValue;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToField\Processor\ProcessorInterface;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToField\Processor\ProcessorPool;
use Magento\Customer\Api\AddressMetadataInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\App\Config\Value;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see ProcessConfigValue
 * @covers ProcessConfigValue::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ProcessConfigValueTest extends \PHPUnit\Framework\TestCase
{
    private const VALUE = 'test_value';
    private const WEBSITE_ID = 1;
    private const SOURCE_MODEL = 'Vendor\ModuleName\Model\Source';

    /**
     * @var AttributeRepositoryInterface|MockObject
     */
    private $attributeRepositoryMock;

    /**
     * @var GetAttributeCode|MockObject
     */
    private $getAttributeCodeMock;

    /**
     * @var InvalidateCheckoutCache|MockObject
     */
    private $invalidateCheckoutCacheMock;

    /**
     * @var ProcessorPool|MockObject
     */
    private $processorPoolMock;

    /**
     * @var Value|MockObject
     */
    private $configValueMock;

    /**
     * @var ProcessConfigValue
     */
    private $subject;

    protected function setUp(): void
    {
        $this->attributeRepositoryMock = $this->createMock(AttributeRepositoryInterface::class);
        $this->getAttributeCodeMock = $this->createMock(GetAttributeCode::class);
        $this->invalidateCheckoutCacheMock = $this->createMock(InvalidateCheckoutCache::class);
        $this->processorPoolMock = $this->createMock(ProcessorPool::class);
        $this->configValueMock = $this->createMock(Value::class);

        $this->subject = new ProcessConfigValue(
            $this->attributeRepositoryMock,
            $this->getAttributeCodeMock,
            $this->invalidateCheckoutCacheMock,
            $this->processorPoolMock
        );
    }

    /**
     * @param int|null $websiteId
     * @dataProvider generalDataProvider
     */
    public function testExecuteWithoutSourceModel(?int $websiteId): void
    {
        $this->configValueMock
            ->expects($this->once())
            ->method('getData')
            ->with('field_config', null)
            ->willReturn([]);

        $this->attributeRepositoryMock->expects($this->never())->method('get');
        $this->invalidateCheckoutCacheMock->expects($this->never())->method('execute');
        $this->processorPoolMock->expects($this->never())->method('get');
        $this->subject->execute($this->configValueMock, self::VALUE, $websiteId);
    }

    /**
     * @param int|null $websiteId
     * @dataProvider generalDataProvider
     */
    public function testExecute(?int $websiteId): void
    {
        $attributeId = '1';

        $attributeMock = $this->createMock(AttributeInterface::class);
        $attributeMock
            ->expects($this->once())
            ->method('getAttributeId')
            ->willReturn($attributeId);

        $processorMock = $this->createMock(ProcessorInterface::class);
        $processorMock
            ->expects($this->once())
            ->method('execute')
            ->with((int) $attributeId, self::VALUE, $websiteId);

        $this->configValueMock
            ->expects($this->once())
            ->method('getData')
            ->with('field_config', null)
            ->willReturn(['source_model' => self::SOURCE_MODEL]);

        $this->getAttributeCodeMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->configValueMock)
            ->willReturn('telephone');

        $this->attributeRepositoryMock
            ->expects($this->once())
            ->method('get')
            ->with(AddressMetadataInterface::ENTITY_TYPE_ADDRESS, 'telephone')
            ->willReturn($attributeMock);

        $this->invalidateCheckoutCacheMock
            ->expects($this->once())
            ->method('execute');

        $this->processorPoolMock
            ->expects($this->once())
            ->method('get')
            ->with(self::SOURCE_MODEL)
            ->willReturn($processorMock);

        $this->subject->execute($this->configValueMock, self::VALUE, $websiteId);
    }

    public function generalDataProvider(): array
    {
        return [[null], [self::WEBSITE_ID]];
    }
}
