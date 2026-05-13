<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Customer\Address\Attribute;

use Amasty\CheckoutCore\Model\Customer\Address\Attribute\CanChangeIfAttributeIsRequired;
use Amasty\CheckoutCore\Model\Customer\Address\Attribute\GetRestrictedCodes;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see CanChangeIfAttributeIsRequired
 * @covers CanChangeIfAttributeIsRequired::execute
 */
class CanChangeIfAttributeIsRequiredTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GetRestrictedCodes|MockObject
     */
    private $getRestrictedCodesMock;

    /**
     * @var CanChangeIfAttributeIsRequired
     */
    private $subject;

    protected function setUp(): void
    {
        $this->getRestrictedCodesMock = $this->createMock(GetRestrictedCodes::class);
        $this->subject = new CanChangeIfAttributeIsRequired($this->getRestrictedCodesMock);
    }

    /**
     * @param string[] $restrictedCodes
     * @param string $attributeCode
     * @param bool $expectedResult
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        array $restrictedCodes,
        string $attributeCode,
        bool $expectedResult
    ): void {
        $this->getRestrictedCodesMock
            ->expects($this->once())
            ->method('execute')
            ->willReturn($restrictedCodes);

        $this->assertEquals($expectedResult, $this->subject->execute($attributeCode));
    }

    public function executeDataProvider(): array
    {
        return [
            [
                ['restricted_code'],
                'restricted_code',
                false
            ],
            [
                [],
                'test_code',
                true
            ],
            [
                ['restricted_code'],
                'test_code',
                true
            ]
        ];
    }
}
