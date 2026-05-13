<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\ConfigManagement;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\UpdateDefaultField;
use Amasty\CheckoutCore\Model\Field\GetDefaultField;
use Amasty\CheckoutCore\Model\ResourceModel\Field as FieldResource;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see UpdateDefaultField
 * @covers UpdateDefaultField::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class UpdateDefaultFieldTest extends \PHPUnit\Framework\TestCase
{
    private const ATTRIBUTE_ID = 1;

    /**
     * @var GetDefaultField|MockObject
     */
    private $getDefaultFieldMock;

    /**
     * @var FieldResource|MockObject
     */
    private $fieldResourceMock;

    /**
     * @var UpdateDefaultField
     */
    private $subject;

    protected function setUp(): void
    {
        $this->getDefaultFieldMock = $this->createMock(GetDefaultField::class);
        $this->fieldResourceMock = $this->createMock(FieldResource::class);

        $this->subject = new UpdateDefaultField(
            $this->getDefaultFieldMock,
            $this->fieldResourceMock
        );
    }

    /**
     * @param bool $isEnabled
     * @param bool $isRequired
     * @dataProvider executeDataProvider
     */
    public function testExecuteWithoutField(bool $isEnabled, bool $isRequired): void
    {
        $this->getDefaultFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_ID)
            ->willReturn(null);

        $this->fieldResourceMock->expects($this->never())->method('save');
        $this->subject->execute(self::ATTRIBUTE_ID, $isEnabled, $isRequired);
    }

    /**
     * @param bool $isEnabled
     * @param bool $isRequired
     * @dataProvider executeDataProvider
     */
    public function testExecute(bool $isEnabled, bool $isRequired): void
    {
        $fieldMock = $this->createMock(Field::class);
        $fieldMock
            ->expects($this->once())
            ->method('setIsEnabled')
            ->with($isEnabled);
        $fieldMock
            ->expects($this->once())
            ->method('setIsRequired')
            ->with($isRequired);

        $this->getDefaultFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_ID)
            ->willReturn($fieldMock);

        $this->fieldResourceMock
            ->expects($this->once())
            ->method('save')
            ->with($fieldMock);

        $this->subject->execute(self::ATTRIBUTE_ID, $isEnabled, $isRequired);
    }

    public function executeDataProvider(): array
    {
        return [
            [false, false],
            [false, true],
            [true, false],
            [true, true]
        ];
    }
}
