<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\OrderAttribute;

use Amasty\CheckoutCore\Model\Field\Form\GetMaxSortOrder;
use Amasty\CheckoutCore\Model\OrderAttribute\UpdateSortOrder;
use Magento\Eav\Model\Attribute;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see UpdateSortOrder
 * @covers UpdateSortOrder::execute
 */
class UpdateSortOrderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GetMaxSortOrder|MockObject
     */
    private $getMaxSortOrderMock;

    /**
     * @var Attribute|MockObject
     */
    private $attributeMock;

    /**
     * @var UpdateSortOrder
     */
    private $subject;

    protected function setUp(): void
    {
        $this->getMaxSortOrderMock = $this->createMock(GetMaxSortOrder::class);
        $this->subject = new UpdateSortOrder($this->getMaxSortOrderMock);

        $this->attributeMock = $this->createMock(Attribute::class);
    }

    public function testExecuteWithNoUpdateFlag(): void
    {
        $this->attributeMock
            ->expects($this->once())
            ->method('hasData')
            ->with(UpdateSortOrder::FLAG_NO_UPDATE)
            ->willReturn(true);

        $this->attributeMock
            ->expects($this->never())
            ->method('__call')
            ->with('setSortingOrder');

        $this->getMaxSortOrderMock->expects($this->never())->method('execute');
        $this->subject->execute($this->attributeMock);
    }

    public function testExecuteWithInvisibleAttribute(): void
    {
        $this->attributeMock
            ->expects($this->once())
            ->method('hasData')
            ->with(UpdateSortOrder::FLAG_NO_UPDATE)
            ->willReturn(false);

        $this->attributeMock
            ->expects($this->once())
            ->method('getIsVisibleOnFront')
            ->willReturn(false);

        $this->attributeMock
            ->expects($this->never())
            ->method('__call')
            ->with('setSortingOrder');

        $this->getMaxSortOrderMock->expects($this->never())->method('execute');
        $this->subject->execute($this->attributeMock);
    }

    /**
     * @param bool $isObjectNew
     * @param string $sortOrder
     * @return void
     * @dataProvider executeWithoutUpdateDataProvider
     */
    public function testExecuteWithoutUpdate(bool $isObjectNew, string $sortOrder): void
    {
        $this->attributeMock
            ->expects($this->once())
            ->method('hasData')
            ->with(UpdateSortOrder::FLAG_NO_UPDATE)
            ->willReturn(false);

        $this->attributeMock
            ->expects($this->once())
            ->method('getIsVisibleOnFront')
            ->willReturn(true);

        $this->attributeMock
            ->expects($this->once())
            ->method('isObjectNew')
            ->with(null)
            ->willReturn($isObjectNew);

        $this->attributeMock
            ->expects($this->once())
            ->method('__call')
            ->with('getSortingOrder')
            ->willReturn($sortOrder);

        $this->getMaxSortOrderMock->expects($this->never())->method('execute');
        $this->subject->execute($this->attributeMock);
    }

    /**
     * @param bool $isObjectNew
     * @param ?string $sortOrder
     * @param int $maxSortOrder
     * @param int $expectedResult
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        bool $isObjectNew,
        ?string $sortOrder,
        int $maxSortOrder,
        int $expectedResult
    ): void {
        $this->attributeMock
            ->expects($this->once())
            ->method('hasData')
            ->with(UpdateSortOrder::FLAG_NO_UPDATE)
            ->willReturn(false);

        $this->getMaxSortOrderMock
            ->expects($this->once())
            ->method('execute')
            ->willReturn($maxSortOrder);

        $this->attributeMock
            ->expects($this->once())
            ->method('getIsVisibleOnFront')
            ->willReturn(true);

        $this->attributeMock
            ->expects($this->once())
            ->method('isObjectNew')
            ->with(null)
            ->willReturn($isObjectNew);

        $this->attributeMock
            ->expects($this->exactly(2))
            ->method('__call')
            ->withConsecutive(
                ['getSortingOrder'],
                ['setSortingOrder', [$expectedResult]]
            )->willReturnOnConsecutiveCalls($sortOrder, $this->attributeMock);

        $this->subject->execute($this->attributeMock);
    }

    public function executeWithoutUpdateDataProvider(): array
    {
        return [
            [false, '5'],
            [true, '5'],
            [false, '0']
        ];
    }

    public function executeDataProvider(): array
    {
        return [
            [true, null, 10, 20],
            [true, '', 10, 20],
            [true, '0', 10, 20],
            [false, null, 10, 20],
            [false, '', 10, 20]
        ];
    }
}
