<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\ConfigManagement\ConfigToField;

use Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToField\GetAttributeCode;
use Magento\Framework\App\Config\Value;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see GetAttributeCode
 * @covers GetAttributeCode::execute
 */
class GetAttributeCodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Value|MockObject
     */
    private $configValueMock;

    protected function setUp(): void
    {
        $this->configValueMock = $this->createMock(Value::class);
    }

    public function testExecuteWithoutAlias(): void
    {
        $this->configValueMock
            ->expects($this->once())
            ->method('__call')
            ->with('getPath')
            ->willReturn('A');

        $this->configValueMock
            ->expects($this->once())
            ->method('getData')
            ->with('field', null)
            ->willReturn('telephone_show');

        $subject = new GetAttributeCode([]);
        $this->assertEquals('telephone', $subject->execute($this->configValueMock));
    }

    public function testExecuteWithAlias(): void
    {
        $this->configValueMock
            ->expects($this->once())
            ->method('__call')
            ->with('getPath')
            ->willReturn('A');

        $this->configValueMock
            ->expects($this->never())
            ->method('getData');

        $subject = new GetAttributeCode(['A' => 'B']);
        $this->assertEquals('B', $subject->execute($this->configValueMock));
    }
}
