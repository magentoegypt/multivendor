<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\Form\Processor;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\CustomerAttributes\UpdateField;
use Amasty\CheckoutCore\Model\Field\Form\GetCustomerAttributes;
use Amasty\CheckoutCore\Model\Field\Form\Processor\CustomerAttributes;
use Amasty\CheckoutCore\Model\Field\Form\SelectFormCodes;
use Amasty\CheckoutCore\Model\Field\SetAttributeFrontendLabel;
use Amasty\CheckoutCore\Model\ModuleEnable;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see CustomerAttributes
 * @covers CustomerAttributes::process
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CustomerAttributesTest extends \PHPUnit\Framework\TestCase
{
    private const DEFAULT_STORE_ID = Field::DEFAULT_STORE_ID;
    private const STORE_ID = 1;
    private const LABEL = 'some label';

    /**
     * @var ModuleEnable|MockObject
     */
    private $moduleEnableMock;

    /**
     * @var GetCustomerAttributes|MockObject
     */
    private $getCustomerAttributesMock;

    /**
     * @var SetAttributeFrontendLabel|MockObject
     */
    private $setAttributeFrontendLabelMock;

    /**
     * @var SelectFormCodes|MockObject
     */
    private $selectFormCodesMock;

    /**
     * @var AttributeResource|MockObject
     */
    private $attributeResourceMock;

    /**
     * @var CustomerAttributes
     */
    private $subject;

    protected function setUp(): void
    {
        $this->moduleEnableMock = $this->createMock(ModuleEnable::class);
        $this->getCustomerAttributesMock = $this->createMock(GetCustomerAttributes::class);
        $this->setAttributeFrontendLabelMock = $this->createMock(SetAttributeFrontendLabel::class);
        $this->selectFormCodesMock = $this->createMock(SelectFormCodes::class);
        $this->attributeResourceMock = $this->createMock(AttributeResource::class);

        $this->subject = new CustomerAttributes(
            $this->moduleEnableMock,
            $this->getCustomerAttributesMock,
            $this->setAttributeFrontendLabelMock,
            $this->selectFormCodesMock,
            $this->attributeResourceMock
        );
    }

    public function testProcessWithModuleDisabled(): void
    {
        $dummyFieldData = ['attribute_id' => 1];

        $this->moduleEnableMock->expects($this->once())->method('isCustomerAttributesEnable')->willReturn(false);
        $this->setAttributeFrontendLabelMock->expects($this->never())->method('execute');
        $this->attributeResourceMock->expects($this->never())->method('save');
        $this->assertEquals([$dummyFieldData], $this->subject->process([$dummyFieldData], self::DEFAULT_STORE_ID));
    }

    public function testProcessWithNoFields(): void
    {
        $this->moduleEnableMock->expects($this->once())->method('isCustomerAttributesEnable')->willReturn(true);
        $this->setAttributeFrontendLabelMock->expects($this->never())->method('execute');
        $this->attributeResourceMock->expects($this->never())->method('save');
        $this->assertEquals([], $this->subject->process([], self::DEFAULT_STORE_ID));
    }

    public function testProcessWithoutAttributes(): void
    {
        $dummyFieldData = ['attribute_id' => 1];

        $this->moduleEnableMock->expects($this->once())->method('isCustomerAttributesEnable')->willReturn(true);
        $this->getCustomerAttributesMock->expects($this->once())->method('execute')->willReturn([]);
        $this->setAttributeFrontendLabelMock->expects($this->never())->method('execute');
        $this->attributeResourceMock->expects($this->never())->method('save');
        $this->assertEquals([$dummyFieldData], $this->subject->process([$dummyFieldData], self::DEFAULT_STORE_ID));
    }

    /**
     * @param MockObject $attributeMock
     * @param array $fields
     * @dataProvider processWithNoMatchingAttributesDataProvider
     */
    public function testProcessWithNoMatchingAttributes(MockObject $attributeMock, array $fields): void
    {
        $this->moduleEnableMock->expects($this->once())->method('isCustomerAttributesEnable')->willReturn(true);
        $this->getCustomerAttributesMock->expects($this->once())->method('execute')->willReturn([$attributeMock]);
        $this->setAttributeFrontendLabelMock->expects($this->never())->method('execute');
        $this->attributeResourceMock->expects($this->never())->method('save');
        $this->assertSame($fields, $this->subject->process($fields, self::DEFAULT_STORE_ID));
    }

    /**
     * @param MockObject $attributeMock
     * @param array $fields
     * @dataProvider processWithFieldWithUseDefaultDataProvider
     */
    public function testProcessWithFieldWithUseDefault(MockObject $attributeMock, array $fields): void
    {
        $this->moduleEnableMock->expects($this->once())->method('isCustomerAttributesEnable')->willReturn(true);
        $this->getCustomerAttributesMock->expects($this->once())->method('execute')->willReturn([$attributeMock]);
        $this->setAttributeFrontendLabelMock->expects($this->never())->method('execute');
        $this->attributeResourceMock->expects($this->never())->method('save');
        $this->assertEquals([], $this->subject->process($fields, self::DEFAULT_STORE_ID));
    }

    /**
     * @param MockObject $attributeMock
     * @param array $fields
     * @param int $expectedSortOrder
     * @param bool $expectedIsRequired
     * @param bool $expectedIsVisibleOnFront
     * @dataProvider processWithMatchingAttributeAndDefaultStoreDataProvider
     */
    public function testProcessWithDefaultStoreId(
        MockObject $attributeMock,
        array $fields,
        int $expectedSortOrder,
        bool $expectedIsRequired,
        bool $expectedIsVisibleOnFront
    ): void {
        $this->moduleEnableMock
            ->expects($this->once())
            ->method('isCustomerAttributesEnable')
            ->willReturn(true);

        $this->getCustomerAttributesMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::DEFAULT_STORE_ID)
            ->willReturn([$attributeMock]);

        $formCodes = ['some_form', 'another_form'];
        $this->selectFormCodesMock
            ->expects($this->once())
            ->method('execute')
            ->willReturn($formCodes);

        $attributeMock
            ->expects($this->exactly(6))
            ->method('setData')
            ->withConsecutive(
                [UpdateField::FLAG_NO_FIELD_UPDATE, true],
                ['sorting_order', $expectedSortOrder],
                ['sort_order', $expectedSortOrder + CustomerAttributes::SORT_ORDER_OFFSET],
                ['used_in_product_listing', $expectedIsVisibleOnFront],
                ['used_in_forms', $formCodes],
                ['required_on_front', false]
            );

        $this->attributeResourceMock
            ->expects($this->once())
            ->method('save')
            ->with($attributeMock);

        $attributeMock
            ->expects($this->once())
            ->method('setIsRequired')
            ->with($expectedIsRequired);

        $this->setAttributeFrontendLabelMock
            ->expects($this->once())
            ->method('execute')
            ->with($attributeMock, self::DEFAULT_STORE_ID, self::LABEL);

        $this->assertEquals([], $this->subject->process($fields, self::DEFAULT_STORE_ID));
    }

    /**
     * @param MockObject $attributeMock
     * @param array $fields
     * @dataProvider processWithMatchingAttributeDataProvider
     */
    public function testProcessWithStoreId(MockObject $attributeMock, array $fields): void
    {
        $this->moduleEnableMock
            ->expects($this->once())
            ->method('isCustomerAttributesEnable')
            ->willReturn(true);

        $this->getCustomerAttributesMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::STORE_ID)
            ->willReturn([$attributeMock]);

        $this->attributeResourceMock
            ->expects($this->once())
            ->method('save')
            ->with($attributeMock);

        $this->setAttributeFrontendLabelMock
            ->expects($this->once())
            ->method('execute')
            ->with($attributeMock, self::STORE_ID, self::LABEL);

        $this->selectFormCodesMock->expects($this->never())->method('execute');
        $attributeMock->expects($this->never())->method('setData');
        $attributeMock->expects($this->never())->method('setIsRequired');
        $this->assertEquals([], $this->subject->process($fields, self::STORE_ID));
    }

    public function processWithNoMatchingAttributesDataProvider(): array
    {
        return [
            [
                $this->createConfiguredMock(Attribute::class, ['getAttributeId' => '1']),
                [
                    2 => ['attribute_id' => 2]
                ]
            ]
        ];
    }

    public function processWithFieldWithUseDefaultDataProvider(): array
    {
        return [
            [
                $this->createConfiguredMock(Attribute::class, ['getAttributeId' => '1']),
                [
                    1 => ['attribute_id' => 1, 'use_default' => 1]
                ]
            ]
        ];
    }

    public function processWithMatchingAttributeAndDefaultStoreDataProvider(): array
    {
        return [
            [
                $this->createConfiguredMock(Attribute::class, ['getAttributeId' => '1']),
                [
                    1 => ['attribute_id' => 1, 'enabled' => 1, 'sort_order' => 0, 'label' => self::LABEL]
                ],
                0,
                false,
                true
            ],
            [
                $this->createConfiguredMock(Attribute::class, ['getAttributeId' => '1']),
                [
                    1 => [
                        'attribute_id'  => 1,
                        'enabled'       => 1,
                        'sort_order'    => 0,
                        'required'      => 1,
                        'label'         => self::LABEL
                    ]
                ],
                0,
                true,
                true
            ],
            [
                $this->createConfiguredMock(Attribute::class, ['getAttributeId' => '1']),
                [
                    1 => ['attribute_id' => 1, 'enabled' => 0, 'sort_order' => 0, 'label' => self::LABEL]
                ],
                0,
                false,
                false
            ],
            [
                $this->createConfiguredMock(Attribute::class, ['getAttributeId' => '1']),
                [
                    1 => [
                        'attribute_id'  => 1,
                        'enabled'       => 0,
                        'sort_order'    => 0,
                        'required'      => 1,
                        'label'         => self::LABEL
                    ]
                ],
                0,
                false,
                false
            ]
        ];
    }

    public function processWithMatchingAttributeDataProvider(): array
    {
        return [
            [
                $this->createConfiguredMock(Attribute::class, ['getAttributeId' => '1']),
                [
                    1 => ['attribute_id' => 1, 'enabled' => 0, 'sort_order' => 0, 'label' => self::LABEL]
                ]
            ],
            [
                $this->createConfiguredMock(Attribute::class, ['getAttributeId' => '1']),
                [
                    1 => [
                        'attribute_id'  => 1,
                        'enabled'       => 0,
                        'sort_order'    => 0,
                        'required'      => 1,
                        'label'         => self::LABEL
                    ]
                ]
            ],
            [
                $this->createConfiguredMock(Attribute::class, ['getAttributeId' => '1']),
                [
                    1 => ['attribute_id' => 1, 'enabled' => 1, 'sort_order' => 0, 'label' => self::LABEL]
                ]
            ],
            [
                $this->createConfiguredMock(Attribute::class, ['getAttributeId' => '1']),
                [
                    1 => [
                        'attribute_id'  => 1,
                        'enabled'       => 1,
                        'sort_order'    => 0,
                        'required'      => 1,
                        'label'         => self::LABEL
                    ]
                ]
            ]
        ];
    }
}
