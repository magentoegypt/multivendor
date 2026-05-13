<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\DuplicateField;
use Amasty\CheckoutCore\Model\FieldFactory;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see DuplicateField
 * @covers DuplicateField::execute
 */
class DuplicateFieldTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FieldFactory|MockObject
     */
    private $fieldFactoryMock;

    /**
     * @var DuplicateField
     */
    private $subject;

    protected function setUp(): void
    {
        $this->fieldFactoryMock = $this->createMock(FieldFactory::class);
        $this->subject = new DuplicateField($this->fieldFactoryMock);
    }

    public function testExecute(): void
    {
        $dummyData = [Field::ID => '1', 'A' => 1, 'B' => 2];
        $expectedDummyData = ['A' => 1, 'B' => 2];

        $fieldMock = $this->createMock(Field::class);
        $fieldMock
            ->expects($this->once())
            ->method('getData')
            ->with('', null)
            ->willReturn($dummyData);

        $resultFieldMock = $this->createMock(Field::class);
        $resultFieldMock
            ->expects($this->once())
            ->method('setData')
            ->with($expectedDummyData, null);

        $this->fieldFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($resultFieldMock);

        $this->assertEquals($resultFieldMock, $this->subject->execute($fieldMock));
    }
}
