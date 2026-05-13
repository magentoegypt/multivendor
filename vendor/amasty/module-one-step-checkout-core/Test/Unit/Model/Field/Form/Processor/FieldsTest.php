<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\Form\Processor;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\Form\Processor\Fields;
use Amasty\CheckoutCore\Model\Field\Form\SaveField;
use Amasty\CheckoutCore\Model\Field\UpdateTelephoneAttribute;
use Amasty\CheckoutCore\Model\ResourceModel\Field\Collection;
use Amasty\CheckoutCore\Model\ResourceModel\Field\CollectionFactory;
use Amasty\CheckoutCore\Model\ResourceModel\GetCustomerAddressAttributeById;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Attribute;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see Fields
 * @covers Fields::process
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class FieldsTest extends \PHPUnit\Framework\TestCase
{
    private const DEFAULT_STORE_ID = Field::DEFAULT_STORE_ID;
    private const TELEPHONE_ATTRIBUTE_CODE = AddressInterface::TELEPHONE;
    private const ATTRIBUTE_ID = 1;

    /**
     * @var Collection|MockObject
     */
    private $fieldCollectionMock;

    /**
     * @var SaveField|MockObject
     */
    private $saveFieldMock;

    /**
     * @var GetCustomerAddressAttributeById|MockObject
     */
    private $getCustomerAddressAttributeByIdMock;

    /**
     * @var UpdateTelephoneAttribute|MockObject
     */
    private $updateTelephoneAttributeMock;

    /**
     * @var Fields
     */
    private $subject;

    protected function setUp(): void
    {
        $this->fieldCollectionMock = $this->createMock(Collection::class);
        $fieldCollectionFactoryMock = $this->createConfiguredMock(
            CollectionFactory::class,
            ['create' => $this->fieldCollectionMock]
        );

        $this->saveFieldMock = $this->createMock(SaveField::class);
        $this->getCustomerAddressAttributeByIdMock = $this->createMock(GetCustomerAddressAttributeById::class);
        $this->updateTelephoneAttributeMock = $this->createMock(UpdateTelephoneAttribute::class);

        $this->subject = new Fields(
            $fieldCollectionFactoryMock,
            $this->saveFieldMock,
            $this->getCustomerAddressAttributeByIdMock,
            $this->updateTelephoneAttributeMock
        );
    }

    public function testProcessWithNoFields(): void
    {
        $this->fieldCollectionMock->expects($this->never())->method('addFilterByStoreId');
        $this->fieldCollectionMock->expects($this->never())->method('getItemByColumnValue');
        $this->saveFieldMock->expects($this->never())->method('execute');
        $this->getCustomerAddressAttributeByIdMock->expects($this->never())->method('execute');
        $this->updateTelephoneAttributeMock->expects($this->never())->method('execute');
        $this->assertEquals([], $this->subject->process([], self::DEFAULT_STORE_ID));
    }

    public function testProcessWithNonDefaultStoreId(): void
    {
        $fields = [self::ATTRIBUTE_ID => ['attribute_id' => self::ATTRIBUTE_ID]];
        $this->fieldCollectionMock->expects($this->never())->method('addFilterByStoreId');
        $this->fieldCollectionMock->expects($this->never())->method('getItemByColumnValue');
        $this->saveFieldMock->expects($this->never())->method('execute');
        $this->getCustomerAddressAttributeByIdMock->expects($this->never())->method('execute');
        $this->updateTelephoneAttributeMock->expects($this->never())->method('execute');
        $this->assertEquals($fields, $this->subject->process($fields, 1));
    }

    /**
     * @param array $fieldData
     * @param array $resultFieldData
     * @dataProvider processDataProvider
     */
    public function testProcess(array $fieldData, array $resultFieldData): void
    {
        $fieldMock = $this->createMock(Field::class);
        $this->mockFieldCollection($fieldMock);

        $this->saveFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with($fieldMock, $resultFieldData);

        $this->getCustomerAddressAttributeByIdMock->expects($this->never())->method('execute');
        $this->updateTelephoneAttributeMock->expects($this->never())->method('execute');
        $this->assertEquals([], $this->subject->process([1 => $fieldData], self::DEFAULT_STORE_ID));
    }

    /**
     * @param array $fieldData
     * @param array $resultFieldData
     * @dataProvider generalDataProvider
     */
    public function testProcessWithNoAttribute(array $fieldData, array $resultFieldData): void
    {
        $fieldMock = $this->createMock(Field::class);
        $this->mockFieldCollection($fieldMock);

        $this->saveFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with($fieldMock, $resultFieldData);

        $this->getCustomerAddressAttributeByIdMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_ID)
            ->willReturn(null);

        $this->updateTelephoneAttributeMock->expects($this->never())->method('execute');
        $this->assertEquals([], $this->subject->process([1 => $fieldData], self::DEFAULT_STORE_ID));
    }

    /**
     * @param array $fieldData
     * @param array $resultFieldData
     * @dataProvider generalDataProvider
     */
    public function testProcessWithWrongAttributeCode(array $fieldData, array $resultFieldData): void
    {
        $fieldMock = $this->createMock(Field::class);
        $this->mockFieldCollection($fieldMock);

        $this->saveFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with($fieldMock, $resultFieldData);

        $attributeMock = $this->createConfiguredMock(Attribute::class, ['getAttributeCode' => 'wrong_code']);
        $this->getCustomerAddressAttributeByIdMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_ID)
            ->willReturn($attributeMock);

        $this->updateTelephoneAttributeMock->expects($this->never())->method('execute');
        $this->assertEquals([], $this->subject->process([1 => $fieldData], self::DEFAULT_STORE_ID));
    }

    /**
     * @param array $fieldData
     * @param array $resultFieldData
     * @dataProvider generalDataProvider
     */
    public function testProcessWithAttribute(array $fieldData, array $resultFieldData): void
    {
        $fieldMock = $this->createMock(Field::class);
        $this->mockFieldCollection($fieldMock);

        $this->saveFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with($fieldMock, $resultFieldData);

        $attributeMock = $this->createConfiguredMock(
            Attribute::class,
            ['getAttributeCode' => self::TELEPHONE_ATTRIBUTE_CODE]
        );

        $this->getCustomerAddressAttributeByIdMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_ID)
            ->willReturn($attributeMock);

        $this->updateTelephoneAttributeMock
            ->expects($this->once())
            ->method('execute')
            ->with($attributeMock);

        $this->assertEquals([], $this->subject->process([1 => $fieldData], self::DEFAULT_STORE_ID));
    }

    public function processDataProvider(): array
    {
        return [
            [
                ['enabled' => 1],
                [
                    'attribute_id'  => self::ATTRIBUTE_ID,
                    'enabled'       => 1,
                    'required'      => 0,
                    'store_id'      => self::DEFAULT_STORE_ID
                ]
            ],
            [
                ['enabled' => 1, 'required' => 1],
                [
                    'attribute_id'  => self::ATTRIBUTE_ID,
                    'enabled'       => 1,
                    'required'      => 1,
                    'store_id'      => self::DEFAULT_STORE_ID
                ]
            ]
        ];
    }

    public function generalDataProvider(): array
    {
        return [
            [
                ['enabled' => 0],
                [
                    'attribute_id'  => self::ATTRIBUTE_ID,
                    'enabled'       => 0,
                    'required'      => 0,
                    'store_id'      => self::DEFAULT_STORE_ID
                ]
            ],
            [
                ['enabled' => 0, 'required' => 1],
                [
                    'attribute_id'  => self::ATTRIBUTE_ID,
                    'enabled'       => 0,
                    'required'      => 0,
                    'store_id'      => self::DEFAULT_STORE_ID
                ]
            ],
        ];
    }

    private function mockFieldCollection(MockObject $fieldMock): void
    {
        $this->fieldCollectionMock
            ->expects($this->once())
            ->method('addFilterByStoreId')
            ->with(self::DEFAULT_STORE_ID);
        $this->fieldCollectionMock
            ->expects($this->once())
            ->method('getItemByColumnValue')
            ->with(Field::ATTRIBUTE_ID, self::ATTRIBUTE_ID)
            ->willReturn($fieldMock);
    }
}
