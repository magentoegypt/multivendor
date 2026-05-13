<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\ConfigManagement\ConfigToAttribute\Processor;

use Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToAttribute\Processor\YesNo;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\CustomerAttributes\UpdateAttribute;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\YesNoOptions;
use Magento\Customer\Model\Attribute;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see YesNo
 * @covers YesNo::execute
 */
class YesNoTest extends \PHPUnit\Framework\TestCase
{
    private const WEBSITE_ID = 1;

    /**
     * @var UpdateAttribute|MockObject
     */
    private $updateAttributeMock;

    /**
     * @var YesNo
     */
    private $subject;

    protected function setUp(): void
    {
        $this->updateAttributeMock = $this->createMock(UpdateAttribute::class);
        $this->subject = new YesNo($this->updateAttributeMock);
    }

    /**
     * @param int $websiteId
     * @return void
     * @dataProvider executeWithUnexpectedValueDataProvider
     */
    public function testExecuteWithUnexpectedValue(int $websiteId): void
    {
        $attributeMock = $this->createMock(Attribute::class);
        $value = 'unexpected_value';

        $this->updateAttributeMock->expects($this->never())->method('execute');
        $this->subject->execute($attributeMock, $value, $websiteId);
    }

    /**
     * @param string $value
     * @param bool $expectedIsEnabled
     * @param bool $expectedIsRequired
     * @param int $websiteId
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        string $value,
        bool $expectedIsEnabled,
        bool $expectedIsRequired,
        int $websiteId
    ): void {
        $attributeMock = $this->createMock(Attribute::class);

        $this->updateAttributeMock
            ->expects($this->once())
            ->method('execute')
            ->with(
                $attributeMock,
                $expectedIsEnabled,
                $expectedIsRequired,
                $websiteId
            );

        $this->subject->execute($attributeMock, $value, $websiteId);
    }

    public function executeWithUnexpectedValueDataProvider(): array
    {
        return [[UpdateAttribute::DEFAULT_WEBSITE_ID], [self::WEBSITE_ID]];
    }

    public function executeDataProvider(): array
    {
        return [
            [YesNoOptions::VALUE_NO, false, false, UpdateAttribute::DEFAULT_WEBSITE_ID],
            [YesNoOptions::VALUE_YES, true, false, UpdateAttribute::DEFAULT_WEBSITE_ID],
            [YesNoOptions::VALUE_NO, false, false, self::WEBSITE_ID],
            [YesNoOptions::VALUE_YES, true, false, self::WEBSITE_ID]
        ];
    }
}
