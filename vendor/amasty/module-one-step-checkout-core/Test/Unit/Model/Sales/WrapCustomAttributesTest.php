<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Sales;

use Amasty\CheckoutCore\Model\Sales\WrapCustomAttributes;
use Magento\Framework\Api\AttributeInterface;
use Magento\Framework\Api\AttributeInterfaceFactory;
use Magento\Framework\Api\CustomAttributesDataInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see WrapCustomAttributes
 * @covers WrapCustomAttributes::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class WrapCustomAttributesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AttributeInterfaceFactory|MockObject
     */
    private $attributeValueFactoryMock;

    /**
     * @var WrapCustomAttributes
     */
    private $subject;

    protected function setUp(): void
    {
        $this->attributeValueFactoryMock = $this->createMock(AttributeInterfaceFactory::class);
        $this->subject = new WrapCustomAttributes($this->attributeValueFactoryMock);
    }

    public function testExecuteWithoutAttributes(): void
    {
        $this->attributeValueFactoryMock->expects($this->never())->method('create');
        $this->assertEquals([], $this->subject->execute([]));
    }

    public function testExecuteWithAttributeValueObject(): void
    {
        $addressData = [
            CustomAttributesDataInterface::CUSTOM_ATTRIBUTES => [
                'some_code' => $this->createMock(AttributeInterface::class)
            ]
        ];

        $this->attributeValueFactoryMock->expects($this->never())->method('create');
        $this->assertEquals($addressData, $this->subject->execute($addressData));
    }

    public function testExecuteWithNonWrappedValue(): void
    {
        $attributeCode = 'some_code';
        $value = 'test';

        $addressData = [
            CustomAttributesDataInterface::CUSTOM_ATTRIBUTES => [
                $attributeCode => $value
            ]
        ];

        $attributeValueMock = $this->createMock(AttributeInterface::class);
        $this->attributeValueFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($attributeValueMock);

        $attributeValueMock
            ->expects($this->once())
            ->method('setAttributeCode')
            ->with($attributeCode)
            ->willReturnSelf();
        $attributeValueMock
            ->expects($this->once())
            ->method('setValue')
            ->with($value)
            ->willReturnSelf();

        $this->assertEquals(
            [
                CustomAttributesDataInterface::CUSTOM_ATTRIBUTES => [
                    $attributeCode => $attributeValueMock
                ]
            ],
            $this->subject->execute($addressData)
        );
    }
}
