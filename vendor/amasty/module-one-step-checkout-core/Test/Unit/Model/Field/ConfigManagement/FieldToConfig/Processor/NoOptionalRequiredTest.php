<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\ConfigManagement\FieldToConfig\Processor;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\FieldToConfig\Processor\NoOptionalRequired;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\FieldToConfig\SaveConfigValue;
use Magento\Config\Model\Config\Source\Nooptreq;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see NoOptionalRequired
 * @covers NoOptionalRequired::execute
 */
class NoOptionalRequiredTest extends \PHPUnit\Framework\TestCase
{
    private const CONFIG_PATH = 'a/b/c';

    /**
     * @var SaveConfigValue|MockObject
     */
    private $saveConfigValueMock;

    /**
     * @var NoOptionalRequired
     */
    private $subject;

    protected function setUp(): void
    {
        $this->saveConfigValueMock = $this->createMock(SaveConfigValue::class);
        $this->subject = new NoOptionalRequired($this->saveConfigValueMock);
    }

    /**
     * @param bool $isEnabled
     * @param bool $isRequired
     * @param string $expectedResult
     * @dataProvider executeDataProvider
     */
    public function testExecute(bool $isEnabled, bool $isRequired, string $expectedResult): void
    {
        $fieldMock = $this->createMock(Field::class);
        $fieldMock
            ->expects($this->once())
            ->method('isEnabled')
            ->willReturn($isEnabled);
        $fieldMock
            ->expects($this->atMost(1))
            ->method('getIsRequired')
            ->willReturn($isRequired);

        $this->saveConfigValueMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::CONFIG_PATH, $expectedResult);

        $this->subject->execute($fieldMock, self::CONFIG_PATH);
    }

    public function executeDataProvider(): array
    {
        return [
            [false, false, Nooptreq::VALUE_NO],
            [false, true, Nooptreq::VALUE_NO],
            [true, false, Nooptreq::VALUE_OPTIONAL],
            [true, true, Nooptreq::VALUE_REQUIRED]
        ];
    }
}
