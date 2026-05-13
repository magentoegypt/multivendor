<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\ConfigManagement\CustomerAttributes;

use Amasty\CheckoutCore\Cache\InvalidateCheckoutCache;
use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\CustomerAttributes\UpdateField;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\UpdateDefaultField;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\UpdateFieldsByWebsiteId;
use Amasty\CheckoutCore\Plugin\Customer\Model\Attribute\SetWebsitePlugin;
use Magento\Customer\Model\Attribute;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see UpdateField
 * @covers UpdateField::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class UpdateFieldTest extends \PHPUnit\Framework\TestCase
{
    private const ATTRIBUTE_ID = 1;
    private const WEBSITE_ID = 2;

    /**
     * @var UpdateDefaultField|MockObject
     */
    private $updateDefaultFieldMock;

    /**
     * @var UpdateFieldsByWebsiteId|MockObject
     */
    private $updateFieldsByWebsiteIdMock;

    /**
     * @var InvalidateCheckoutCache|MockObject
     */
    private $invalidateCheckoutCacheMock;

    /**
     * @var UpdateField
     */
    private $subject;

    protected function setUp(): void
    {
        $this->updateDefaultFieldMock = $this->createMock(UpdateDefaultField::class);
        $this->updateFieldsByWebsiteIdMock = $this->createMock(UpdateFieldsByWebsiteId::class);
        $this->invalidateCheckoutCacheMock = $this->createMock(InvalidateCheckoutCache::class);

        $this->subject = new UpdateField(
            $this->updateDefaultFieldMock,
            $this->updateFieldsByWebsiteIdMock,
            $this->invalidateCheckoutCacheMock
        );
    }

    public function testExecuteWithFlag(): void
    {
        $attributeMock = $this->createMock(Attribute::class);
        $attributeMock
            ->expects($this->once())
            ->method('hasData')
            ->with(UpdateField::FLAG_NO_FIELD_UPDATE)
            ->willReturn(true);

        $fieldMock = $this->createMock(Field::class);
        $fieldMock->expects($this->never())->method('setIsEnabled');
        $fieldMock->expects($this->never())->method('setIsRequired');
        $this->updateDefaultFieldMock->expects($this->never())->method('execute');
        $this->updateFieldsByWebsiteIdMock->expects($this->never())->method('execute');
        $this->invalidateCheckoutCacheMock->expects($this->never())->method('execute');
        $this->subject->execute($attributeMock);
    }

    /**
     * @param string|null $isVisible
     * @param string|null $isRequired
     * @param bool $expectedIsEnabled
     * @param bool $expectedIsRequired
     * @dataProvider executeDataProvider
     */
    public function testExecuteInDefaultScope(
        ?string $isVisible,
        ?string $isRequired,
        bool $expectedIsEnabled,
        bool $expectedIsRequired
    ): void {
        $attributeMock = $this->createMock(Attribute::class);
        $attributeMock
            ->expects($this->once())
            ->method('hasData')
            ->with(UpdateField::FLAG_NO_FIELD_UPDATE)
            ->willReturn(false);
        $attributeMock
            ->expects($this->once())
            ->method('getAttributeId')
            ->willReturn((string) self::ATTRIBUTE_ID);
        $attributeMock
            ->expects($this->atMost(3))
            ->method('getData')
            ->willReturnMap([
                [SetWebsitePlugin::KEY_WEBSITE, null, null],
                ['is_visible', null, $isVisible],
                ['is_required', null, $isRequired]
            ]);

        $this->updateDefaultFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_ID, $expectedIsEnabled, $expectedIsRequired);

        $this->invalidateCheckoutCacheMock
            ->expects($this->once())
            ->method('execute');

        $this->updateFieldsByWebsiteIdMock->expects($this->never())->method('execute');
        $this->subject->execute($attributeMock);
    }

    /**
     * @param string|null $isVisible
     * @param string|null $isRequired
     * @param bool $expectedIsEnabled
     * @param bool $expectedIsRequired
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecuteInWebsiteScope(
        ?string $isVisible,
        ?string $isRequired,
        bool $expectedIsEnabled,
        bool $expectedIsRequired
    ): void {
        $websiteMock = $this->createConfiguredMock(Website::class, ['getId' => (string) self::WEBSITE_ID]);

        $attributeMock = $this->createMock(Attribute::class);
        $attributeMock
            ->expects($this->once())
            ->method('hasData')
            ->with(UpdateField::FLAG_NO_FIELD_UPDATE)
            ->willReturn(false);
        $attributeMock
            ->expects($this->once())
            ->method('getAttributeId')
            ->willReturn((string) self::ATTRIBUTE_ID);
        $attributeMock
            ->expects($this->once())
            ->method('getData')
            ->with(SetWebsitePlugin::KEY_WEBSITE, null)
            ->willReturn($websiteMock);
        $attributeMock
            ->expects($this->once())
            ->method('getIsVisible')
            ->willReturn($isVisible);
        $attributeMock
            ->expects($this->atMost(1))
            ->method('getIsRequired')
            ->willReturn($isRequired);

        $this->updateFieldsByWebsiteIdMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_ID, self::WEBSITE_ID, $expectedIsEnabled, $expectedIsRequired);

        $this->invalidateCheckoutCacheMock
            ->expects($this->once())
            ->method('execute');

        $this->updateDefaultFieldMock->expects($this->never())->method('execute');
        $this->subject->execute($attributeMock);
    }

    public function executeDataProvider(): array
    {
        return [
            [null, null, false, false],
            [null, '0', false, false],
            [null, '1', false, false],
            ['0', null, false, false],
            ['0', '0', false, false],
            ['0', '1', false, false],
            ['1', null, true, false],
            ['1', '0', true, false],
            ['1', '1', true, true]
        ];
    }
}
