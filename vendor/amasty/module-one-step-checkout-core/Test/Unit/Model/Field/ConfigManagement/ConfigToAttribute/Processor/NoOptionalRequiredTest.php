<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\ConfigManagement\ConfigToAttribute\Processor;

use Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToAttribute\Processor\NoOptionalRequired;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\CustomerAttributes\UpdateAttribute;
use Magento\Config\Model\Config\Source\Nooptreq;
use Magento\Customer\Model\Attribute;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see NoOptionalRequired
 * @covers NoOptionalRequired::execute
 */
class NoOptionalRequiredTest extends \PHPUnit\Framework\TestCase
{
    private const WEBSITE_ID = 1;

    /**
     * @var UpdateAttribute|MockObject
     */
    private $updateAttributeMock;

    /**
     * @var NoOptionalRequired
     */
    private $subject;

    protected function setUp(): void
    {
        $this->updateAttributeMock = $this->createMock(UpdateAttribute::class);
        $this->subject = new NoOptionalRequired($this->updateAttributeMock);
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

    public function executeDataProvider(): array
    {
        return [
            [Nooptreq::VALUE_NO, false, false, UpdateAttribute::DEFAULT_WEBSITE_ID],
            [Nooptreq::VALUE_OPTIONAL, true, false, UpdateAttribute::DEFAULT_WEBSITE_ID],
            [Nooptreq::VALUE_REQUIRED, true, true, UpdateAttribute::DEFAULT_WEBSITE_ID],
            [Nooptreq::VALUE_NO, false, false, self::WEBSITE_ID],
            [Nooptreq::VALUE_OPTIONAL, true, false, self::WEBSITE_ID],
            [Nooptreq::VALUE_REQUIRED, true, true, self::WEBSITE_ID]
        ];
    }
}
