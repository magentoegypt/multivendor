<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\ConfigManagement\CustomerAttributes;

use Amasty\CheckoutCore\Model\Customer\Address\Attribute\CanChangeIfAttributeIsRequired;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\CustomerAttributes\UpdateAttribute;
use Magento\Customer\Model\Attribute;
use Magento\Customer\Model\ResourceModel\Attribute as AttributeResource;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see UpdateAttribute
 * @covers UpdateAttribute::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.ElseExpression)
 */
class UpdateAttributeTest extends \PHPUnit\Framework\TestCase
{
    private const WEBSITE_ID = 1;
    private const ATTRIBUTE_CODE = 'test_code';

    /**
     * @var WebsiteRepositoryInterface|MockObject
     */
    private $websiteRepositoryTest;

    /**
     * @var AttributeResource|MockObject
     */
    private $attributeResourceMock;

    /**
     * @var CanChangeIfAttributeIsRequired|MockObject
     */
    private $canChangeIfAttributeIsRequiredMock;

    /**
     * @var Attribute|MockObject
     */
    private $attributeMock;

    /**
     * @var UpdateAttribute
     */
    private $subject;

    protected function setUp(): void
    {
        $this->websiteRepositoryTest = $this->createMock(WebsiteRepositoryInterface::class);
        $this->attributeResourceMock = $this->createMock(AttributeResource::class);
        $this->canChangeIfAttributeIsRequiredMock = $this->createMock(CanChangeIfAttributeIsRequired::class);

        $this->subject = new UpdateAttribute(
            $this->websiteRepositoryTest,
            $this->attributeResourceMock,
            $this->canChangeIfAttributeIsRequiredMock
        );

        $this->attributeMock = $this->createMock(Attribute::class);

        $this->attributeMock
            ->expects($this->once())
            ->method('getAttributeCode')
            ->willReturn(self::ATTRIBUTE_CODE);
    }

    /**
     * @param bool $isEnabled
     * @param bool $isRequired
     * @param bool $canChangeRequiredValue
     * @return void
     * @dataProvider generalDataProvider
     */
    public function testExecute(bool $isEnabled, bool $isRequired, bool $canChangeRequiredValue): void
    {
        $this->attributeMock
            ->expects($this->once())
            ->method('setData')
            ->with('is_visible', $isEnabled);

        if ($canChangeRequiredValue) {
            $this->attributeMock
                ->expects($this->once())
                ->method('setIsRequired')
                ->with($isRequired);
        } else {
            $this->attributeMock
                ->expects($this->never())
                ->method('setIsRequired');
        }

        $this->canChangeIfAttributeIsRequiredMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_CODE)
            ->willReturn($canChangeRequiredValue);

        $this->attributeResourceMock
            ->expects($this->once())
            ->method('save')
            ->with($this->attributeMock);

        $this->attributeMock->expects($this->never())->method('setWebsite');
        $this->websiteRepositoryTest->expects($this->never())->method('getById');

        $this->subject->execute(
            $this->attributeMock,
            $isEnabled,
            $isRequired,
            UpdateAttribute::DEFAULT_WEBSITE_ID
        );
    }

    /**
     * @param bool $isEnabled
     * @param bool $isRequired
     * @param bool $canChangeRequiredValue
     * @return void
     * @dataProvider generalDataProvider
     */
    public function testExecuteWithWebsite(bool $isEnabled, bool $isRequired, bool $canChangeRequiredValue): void
    {
        $websiteMock = $this->createMock(WebsiteInterface::class);

        if ($canChangeRequiredValue) {
            $this->attributeMock
                ->expects($this->exactly(2))
                ->method('setData')
                ->withConsecutive(
                    ['scope_is_visible', $isEnabled],
                    ['scope_is_required', $isRequired]
                );
        } else {
            $this->attributeMock
                ->expects($this->once())
                ->method('setData')
                ->with('scope_is_visible', $isEnabled);
        }

        $this->websiteRepositoryTest
            ->expects($this->once())
            ->method('getById')
            ->with(self::WEBSITE_ID)
            ->willReturn($websiteMock);

        $this->attributeMock
            ->expects($this->once())
            ->method('setWebsite')
            ->with($websiteMock);

        $this->canChangeIfAttributeIsRequiredMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_CODE)
            ->willReturn($canChangeRequiredValue);

        $this->attributeResourceMock
            ->expects($this->once())
            ->method('save')
            ->with($this->attributeMock);

        $this->subject->execute(
            $this->attributeMock,
            $isEnabled,
            $isRequired,
            self::WEBSITE_ID
        );
    }

    public function generalDataProvider(): array
    {
        return [
            [false, false, false],
            [false, false, true],
            [false, true, false],
            [false, true, true],
            [true, false, false],
            [true, false, true],
            [true, true, false],
            [true, true, true]
        ];
    }
}
