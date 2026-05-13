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
use Amasty\CheckoutCore\Model\ResourceModel\GetCustomerAddressAttributeById;
use Magento\Customer\Model\Attribute;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see GetAttributeCode
 * @covers GetAttributeCode::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class GetAttributeCodeTest extends \PHPUnit\Framework\TestCase
{
    private const ATTRIBUTE_ID = 1;

    /**
     * @var GetCustomerAddressAttributeById|MockObject
     */
    private $getCustomerAddressAttributeByIdMock;

    /**
     * @var Field|MockObject
     */
    private $fieldMock;

    /**
     * @var GetAttributeCode
     */
    private $subject;

    protected function setUp(): void
    {
        $this->getCustomerAddressAttributeByIdMock = $this->createMock(GetCustomerAddressAttributeById::class);
        $this->fieldMock = $this->createMock(Field::class);

        $this->subject = new GetAttributeCode($this->getCustomerAddressAttributeByIdMock);
    }

    public function testExecuteWithoutAttributeId(): void
    {
        $this->fieldMock
            ->expects($this->once())
            ->method('getAttributeId')
            ->willReturn(null);

        $this->getCustomerAddressAttributeByIdMock->expects($this->never())->method('execute');
        $this->assertNull($this->subject->execute($this->fieldMock));
    }

    public function testExecuteWithoutAttribute(): void
    {
        $this->fieldMock
            ->expects($this->once())
            ->method('getAttributeId')
            ->willReturn(self::ATTRIBUTE_ID);

        $this->getCustomerAddressAttributeByIdMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_ID)
            ->willReturn(null);

        $this->assertNull($this->subject->execute($this->fieldMock));
    }

    public function testExecute(): void
    {
        $this->fieldMock
            ->expects($this->once())
            ->method('getAttributeId')
            ->willReturn(self::ATTRIBUTE_ID);

        $attributeMock = $this->createConfiguredMock(Attribute::class, ['getAttributeCode' => 'telephone']);
        $this->getCustomerAddressAttributeByIdMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_ID)
            ->willReturn($attributeMock);

        $this->assertEquals('telephone', $this->subject->execute($this->fieldMock));
    }
}
