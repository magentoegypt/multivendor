<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\Form;

use Amasty\CheckoutCore\Model\Customer\Address\Attribute\CanChangeIfAttributeIsRequired;
use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\Form\GetAllowedKeys;
use Amasty\CheckoutCore\Model\ResourceModel\GetCustomerAddressAttributeById;
use Magento\Customer\Model\Attribute;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see GetAllowedKeys
 * @covers GetAllowedKeys::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class GetAllowedKeysTest extends \PHPUnit\Framework\TestCase
{
    private const ATTRIBUTE_ID = 1;
    private const ATTRIBUTE_CODE = 'test_code';

    /**
     * @var CanChangeIfAttributeIsRequired|MockObject
     */
    private $canChangeIfAttributeIsRequiredMock;

    /**
     * @var GetCustomerAddressAttributeById|MockObject
     */
    private $getCustomerAddressAttributeById;

    protected function setUp(): void
    {
        $this->canChangeIfAttributeIsRequiredMock = $this->createMock(CanChangeIfAttributeIsRequired::class);
        $this->getCustomerAddressAttributeById = $this->createMock(GetCustomerAddressAttributeById::class);

        $attributeMock = $this->createConfiguredMock(
            Attribute::class,
            ['getAttributeCode' => self::ATTRIBUTE_CODE]
        );

        $this->getCustomerAddressAttributeById
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_ID)
            ->willReturn($attributeMock);
    }

    /**
     * @param array $allowedKeys
     * @param array $fieldData
     * @param array $expectedResult
     * @return void
     * @dataProvider executeWithDisabledFieldDataProvider
     */
    public function testExecuteWithDisabledField(array $allowedKeys, array $fieldData, array $expectedResult): void
    {
        $this->canChangeIfAttributeIsRequiredMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_CODE)
            ->willReturn(true);

        $subject = new GetAllowedKeys(
            $this->canChangeIfAttributeIsRequiredMock,
            $this->getCustomerAddressAttributeById,
            $allowedKeys
        );

        $this->assertEquals($expectedResult, $subject->execute($fieldData));
    }

    /**
     * @param bool $canChangeRequiredValue
     * @param array $expectedResult
     * @return void
     * @dataProvider executeWithRestrictedAttributeDataProvider
     */
    public function testExecuteWithRestrictedAttribute(
        bool $canChangeRequiredValue,
        array $expectedResult
    ): void {
        $allowedKeys = [
            Field::ENABLED      => Field::ENABLED,
            Field::ATTRIBUTE_ID => Field::ATTRIBUTE_ID,
            Field::REQUIRED     => Field::REQUIRED
        ];

        $fieldData = [Field::ENABLED => '1', Field::ATTRIBUTE_ID => self::ATTRIBUTE_ID, Field::REQUIRED => 1];

        $this->canChangeIfAttributeIsRequiredMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_CODE)
            ->willReturn($canChangeRequiredValue);

        $subject = new GetAllowedKeys(
            $this->canChangeIfAttributeIsRequiredMock,
            $this->getCustomerAddressAttributeById,
            $allowedKeys
        );

        $this->assertEquals($expectedResult, $subject->execute($fieldData));
    }

    public function testExecute(): void
    {
        $allowedKeys = ['first' => 'first', 'second' => 'second'];

        $this->canChangeIfAttributeIsRequiredMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_CODE)
            ->willReturn(true);

        $subject = new GetAllowedKeys(
            $this->canChangeIfAttributeIsRequiredMock,
            $this->getCustomerAddressAttributeById,
            $allowedKeys
        );

        $this->assertEquals(['first', 'second'], $subject->execute([Field::ATTRIBUTE_ID => self::ATTRIBUTE_ID]));
    }

    public function executeWithDisabledFieldDataProvider(): array
    {
        return [
            [
                [
                    Field::ENABLED      => Field::ENABLED,
                    Field::SORT_ORDER   => Field::SORT_ORDER,
                    Field::ATTRIBUTE_ID => Field::ATTRIBUTE_ID
                ],
                [Field::ENABLED => '0', Field::SORT_ORDER => 10, Field::ATTRIBUTE_ID => self::ATTRIBUTE_ID],
                [Field::ENABLED, Field::ATTRIBUTE_ID]
            ],
            [
                [
                    Field::ENABLED      => Field::ENABLED,
                    Field::SORT_ORDER   => Field::SORT_ORDER,
                    Field::ATTRIBUTE_ID => Field::ATTRIBUTE_ID
                ],
                [Field::ENABLED => '1', Field::SORT_ORDER => 10, Field::ATTRIBUTE_ID => self::ATTRIBUTE_ID],
                [Field::ENABLED, Field::SORT_ORDER, Field::ATTRIBUTE_ID]
            ],
        ];
    }

    public function executeWithRestrictedAttributeDataProvider(): array
    {
        return [
            [
                false,
                [Field::ENABLED, Field::ATTRIBUTE_ID]
            ],
            [
                true,
                [Field::ENABLED, Field::ATTRIBUTE_ID, Field::REQUIRED]
            ]
        ];
    }
}
