<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\Form\Processor;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\DeleteAttributeFrontendLabel;
use Amasty\CheckoutCore\Model\Field\Form\Processor\FieldsByStore;
use Amasty\CheckoutCore\Model\Field\Form\Processor\FieldsByStore\CanUseDefaultField;
use Amasty\CheckoutCore\Model\Field\Form\SaveField;
use Amasty\CheckoutCore\Model\FieldFactory;
use Amasty\CheckoutCore\Model\ResourceModel\Field as FieldResource;
use Amasty\CheckoutCore\Model\ResourceModel\Field\Collection;
use Amasty\CheckoutCore\Model\ResourceModel\Field\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see FieldsByStore
 * @covers FieldsByStore::process
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class FieldsByStoreTest extends \PHPUnit\Framework\TestCase
{
    private const DEFAULT_STORE_ID = Field::DEFAULT_STORE_ID;
    private const STORE_ID = 1;
    private const ATTRIBUTE_ID = 1;

    private const ENABLED_NO = '0';
    private const ENABLED_YES = '1';

    /**
     * @var Collection|MockObject
     */
    private $fieldCollectionMock;

    /**
     * @var Collection|MockObject
     */
    private $defaultFieldCollectionMock;

    /**
     * @var SaveField|MockObject
     */
    private $saveFieldMock;

    /**
     * @var FieldFactory|MockObject
     */
    private $fieldFactoryMock;

    /**
     * @var FieldResource|MockObject
     */
    private $fieldResourceMock;

    /**
     * @var CanUseDefaultField|MockObject
     */
    private $canUseDefaultFieldMock;

    /**
     * @var FieldsByStore
     */
    private $subject;

    protected function setUp(): void
    {
        $this->fieldCollectionMock = $this->createMock(Collection::class);
        $this->defaultFieldCollectionMock = $this->createMock(Collection::class);
        $fieldCollectionFactoryMock = $this->createMock(CollectionFactory::class);
        $fieldCollectionFactoryMock
            ->method('create')
            ->willReturnOnConsecutiveCalls(
                $this->fieldCollectionMock,
                $this->defaultFieldCollectionMock
            );

        $this->saveFieldMock = $this->createMock(SaveField::class);
        $this->fieldFactoryMock = $this->createMock(FieldFactory::class);
        $this->fieldResourceMock = $this->createMock(FieldResource::class);
        $this->canUseDefaultFieldMock = $this->createMock(CanUseDefaultField::class);
        $deleteAttributeFrontendLabelMock = $this->createMock(DeleteAttributeFrontendLabel::class);

        $this->subject = new FieldsByStore(
            $fieldCollectionFactoryMock,
            $this->saveFieldMock,
            $this->fieldFactoryMock,
            $this->fieldResourceMock,
            $this->canUseDefaultFieldMock,
            $deleteAttributeFrontendLabelMock
        );
    }

    public function testProcessWithNoFields(): void
    {
        $this->fieldCollectionMock->expects($this->never())->method('addFilterByStoreId');
        $this->fieldCollectionMock->expects($this->never())->method('getItemByColumnValue');
        $this->defaultFieldCollectionMock->expects($this->never())->method('addFilterByStoreId');
        $this->defaultFieldCollectionMock->expects($this->never())->method('getItemByColumnValue');
        $this->saveFieldMock->expects($this->never())->method('execute');
        $this->fieldFactoryMock->expects($this->never())->method('create');
        $this->fieldResourceMock->expects($this->never())->method('delete');
        $this->canUseDefaultFieldMock->expects($this->never())->method('execute');
        $this->assertEquals([], $this->subject->process([], self::STORE_ID));
    }

    public function testProcessWithDefaultStoreId(): void
    {
        $fields = [self::ATTRIBUTE_ID => ['attribute_id' => self::ATTRIBUTE_ID]];

        $this->fieldCollectionMock->expects($this->never())->method('addFilterByStoreId');
        $this->fieldCollectionMock->expects($this->never())->method('getItemByColumnValue');
        $this->defaultFieldCollectionMock->expects($this->never())->method('addFilterByStoreId');
        $this->defaultFieldCollectionMock->expects($this->never())->method('getItemByColumnValue');
        $this->saveFieldMock->expects($this->never())->method('execute');
        $this->fieldFactoryMock->expects($this->never())->method('create');
        $this->fieldResourceMock->expects($this->never())->method('delete');
        $this->canUseDefaultFieldMock->expects($this->never())->method('execute');
        $this->assertEquals($fields, $this->subject->process($fields, self::DEFAULT_STORE_ID));
    }

    /**
     * @param array $fieldData
     * @param array $resultFieldData
     * @dataProvider processWithoutUseDefaultDataProvider
     */
    public function testProcessWithoutUseDefault(array $fieldData, array $resultFieldData): void
    {
        $fieldMock = $this->createMock(Field::class);
        $this->fieldFactoryMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($fieldMock);

        $this->fieldCollectionMock
            ->expects($this->once())
            ->method('addFilterByStoreId')
            ->with(self::STORE_ID);
        $this->fieldCollectionMock
            ->expects($this->once())
            ->method('getItemByColumnValue')
            ->with(Field::ATTRIBUTE_ID, self::ATTRIBUTE_ID)
            ->willReturn(null);

        $defaultFieldMock = $this->createMock(Field::class);
        $this->defaultFieldCollectionMock
            ->expects($this->once())
            ->method('addFilterByStoreId')
            ->with(Field::DEFAULT_STORE_ID);
        $this->defaultFieldCollectionMock
            ->expects($this->once())
            ->method('getItemByColumnValue')
            ->with(Field::ATTRIBUTE_ID, self::ATTRIBUTE_ID)
            ->willReturn($defaultFieldMock);

        $this->canUseDefaultFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with(null, $defaultFieldMock, $fieldData)
            ->willReturn(false);

        $this->saveFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with($fieldMock, $resultFieldData);

        $this->fieldResourceMock->expects($this->never())->method('delete');
        $this->assertEquals([], $this->subject->process([self::ATTRIBUTE_ID => $fieldData], self::STORE_ID));
    }

    /**
     * @param array $fieldData
     * @param array $resultFieldData
     * @dataProvider processWithoutUseDefaultDataProvider
     */
    public function testProcessWithoutUseDefaultWithField(array $fieldData, array $resultFieldData): void
    {
        $fieldMock = $this->createMock(Field::class);

        $this->fieldCollectionMock
            ->expects($this->once())
            ->method('addFilterByStoreId')
            ->with(self::STORE_ID);
        $this->fieldCollectionMock
            ->expects($this->once())
            ->method('getItemByColumnValue')
            ->with(Field::ATTRIBUTE_ID, self::ATTRIBUTE_ID)
            ->willReturn($fieldMock);

        $defaultFieldMock = $this->createMock(Field::class);
        $this->defaultFieldCollectionMock
            ->expects($this->once())
            ->method('addFilterByStoreId')
            ->with(Field::DEFAULT_STORE_ID);
        $this->defaultFieldCollectionMock
            ->expects($this->once())
            ->method('getItemByColumnValue')
            ->with(Field::ATTRIBUTE_ID, self::ATTRIBUTE_ID)
            ->willReturn($defaultFieldMock);

        $this->canUseDefaultFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with($fieldMock, $defaultFieldMock, $fieldData)
            ->willReturn(false);

        $this->saveFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with($fieldMock, $resultFieldData);

        $this->fieldFactoryMock->expects($this->never())->method('create');
        $this->fieldResourceMock->expects($this->never())->method('delete');
        $this->assertEquals([], $this->subject->process([self::ATTRIBUTE_ID => $fieldData], self::STORE_ID));
    }

    /**
     * @param array $fieldData
     * @param MockObject $defaultFieldMock
     * @dataProvider processWithUseDefaultDataProvider
     */
    public function testProcessWithUseDefault(array $fieldData, MockObject $defaultFieldMock): void
    {
        $this->fieldCollectionMock
            ->expects($this->once())
            ->method('addFilterByStoreId')
            ->with(self::STORE_ID);
        $this->fieldCollectionMock
            ->expects($this->once())
            ->method('getItemByColumnValue')
            ->with(Field::ATTRIBUTE_ID, self::ATTRIBUTE_ID)
            ->willReturn(null);
        $this->defaultFieldCollectionMock
            ->expects($this->once())
            ->method('addFilterByStoreId')
            ->with(Field::DEFAULT_STORE_ID);
        $this->defaultFieldCollectionMock
            ->expects($this->once())
            ->method('getItemByColumnValue')
            ->with(Field::ATTRIBUTE_ID, self::ATTRIBUTE_ID)
            ->willReturn($defaultFieldMock);

        $this->canUseDefaultFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with(null, $defaultFieldMock, $fieldData)
            ->willReturn(true);

        $this->fieldFactoryMock->expects($this->never())->method('create');
        $this->fieldResourceMock->expects($this->never())->method('delete');
        $this->saveFieldMock->expects($this->never())->method('execute');
        $this->assertEquals([], $this->subject->process([self::ATTRIBUTE_ID => $fieldData], self::STORE_ID));
    }

    /**
     * @param array $fieldData
     * @param MockObject $fieldMock
     * @dataProvider processWithUseDefaultDataProvider
     */
    public function testProcessWithUseDefaultWithField(array $fieldData, MockObject $fieldMock): void
    {
        $this->fieldCollectionMock
            ->expects($this->once())
            ->method('addFilterByStoreId')
            ->with(self::STORE_ID);
        $this->fieldCollectionMock
            ->expects($this->once())
            ->method('getItemByColumnValue')
            ->with(Field::ATTRIBUTE_ID, self::ATTRIBUTE_ID)
            ->willReturn($fieldMock);

        $defaultFieldMock = $this->createMock(Field::class);
        $this->defaultFieldCollectionMock
            ->expects($this->once())
            ->method('addFilterByStoreId')
            ->with(Field::DEFAULT_STORE_ID);
        $this->defaultFieldCollectionMock
            ->expects($this->once())
            ->method('getItemByColumnValue')
            ->with(Field::ATTRIBUTE_ID, self::ATTRIBUTE_ID)
            ->willReturn($defaultFieldMock);

        $this->canUseDefaultFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with($fieldMock, $defaultFieldMock, $fieldData)
            ->willReturn(true);

        $this->fieldResourceMock
            ->expects($this->once())
            ->method('delete')
            ->with($fieldMock);

        $this->fieldFactoryMock->expects($this->never())->method('create');
        $this->saveFieldMock->expects($this->never())->method('execute');
        $this->assertEquals([], $this->subject->process([self::ATTRIBUTE_ID => $fieldData], self::STORE_ID));
    }

    public function processWithoutUseDefaultDataProvider(): array
    {
        return [
            [
                ['attribute_id' => self::ATTRIBUTE_ID, 'enabled' => self::ENABLED_NO],
                [
                    'attribute_id'  => self::ATTRIBUTE_ID,
                    'enabled'       => self::ENABLED_NO,
                    'required'      => false,
                    'store_id'      => self::STORE_ID
                ]
            ],
            [
                ['attribute_id' => self::ATTRIBUTE_ID, 'enabled' => self::ENABLED_NO, 'required' => 1],
                [
                    'attribute_id'  => self::ATTRIBUTE_ID,
                    'enabled'       => self::ENABLED_NO,
                    'required'      => false,
                    'store_id'      => self::STORE_ID
                ]
            ],
            [
                ['attribute_id' => self::ATTRIBUTE_ID, 'enabled' => self::ENABLED_YES],
                [
                    'attribute_id'  => self::ATTRIBUTE_ID,
                    'enabled'       => self::ENABLED_YES,
                    'required'      => false,
                    'store_id'      => self::STORE_ID
                ]
            ],
            [
                ['attribute_id' => self::ATTRIBUTE_ID, 'enabled' => self::ENABLED_YES, 'required' => 1],
                [
                    'attribute_id'  => self::ATTRIBUTE_ID,
                    'enabled'       => self::ENABLED_YES,
                    'required'      => true,
                    'store_id'      => self::STORE_ID
                ]
            ]
        ];
    }

    public function processWithUseDefaultDataProvider(): array
    {
        $fieldMock = $this->createMock(Field::class);

        $data = [];
        $data[] = [
            ['enabled' => self::ENABLED_NO, 'required' => 1, 'use_default' => 1],
            $fieldMock
        ];

        $fieldMock = $this->createMock(Field::class);

        $data[] = [
            ['enabled' => self::ENABLED_YES, 'required' => 1, 'use_default' => 1],
            $fieldMock
        ];

        return $data;
    }
}
