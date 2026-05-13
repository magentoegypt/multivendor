<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field;

use Amasty\CheckoutCore\Model\Field\UpdateTelephoneAttribute;
use Magento\Customer\Model\Attribute;
use Magento\Eav\Model\ResourceModel\Entity\Attribute as AttributeResource;
use PHPUnit\Framework\MockObject\MockObject;
use Amasty\CheckoutCore\Model\Config;

/**
 * @see UpdateTelephoneAttribute
 * @covers UpdateTelephoneAttribute::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class UpdateTelephoneAttributeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UpdateTelephoneAttribute
     */
    private $subject;

    /**
     * @var AttributeResource|MockObject
     */
    private $attributeResourceMock;

    /**
     * @var Config|MockObject
     */
    private $configProviderMock;

    protected function setUp(): void
    {
        $this->attributeResourceMock = $this->createMock(AttributeResource::class);
        $this->configProviderMock = $this->createMock(Config::class);

        $this->subject = new UpdateTelephoneAttribute(
            $this->attributeResourceMock,
            $this->configProviderMock
        );
    }

    public function testExecuteWithAttributeWithCorrectCode(): void
    {
        $attributeMock = $this->createMock(Attribute::class);

        $this->configProviderMock
            ->expects($this->once())
            ->method('saveTelephoneOption')
            ->with('');

        $attributeMock
            ->expects($this->once())
            ->method('setIsRequired')
            ->with(false);

        $this->attributeResourceMock
            ->expects($this->once())
            ->method('save')
            ->with($attributeMock);

        $this->subject->execute($attributeMock);
    }
}
