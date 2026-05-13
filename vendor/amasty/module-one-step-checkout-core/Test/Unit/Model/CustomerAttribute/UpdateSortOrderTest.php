<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\CustomerAttribute;

use Amasty\CheckoutCore\Model\CustomerAttribute\UpdateSortOrder;
use Amasty\CheckoutCore\Model\Field\Form\GetMaxSortOrder;
use Magento\Customer\Model\Attribute;
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

    public function testExecuteWithInvisibleAttribute(): void
    {
        $this->attributeMock
            ->expects($this->once())
            ->method('getData')
            ->with('used_in_product_listing', null)
            ->willReturn('0');

        $this->getMaxSortOrderMock->expects($this->never())->method('execute');
        $this->attributeMock->expects($this->never())->method('setData');
        $this->subject->execute($this->attributeMock);
    }

    /**
     * @param bool $isObjectNew
     * @param string $sortingOrder
     * @return void
     * @dataProvider executeWithoutUpdateDataProvider
     */
    public function testExecuteWithoutUpdate(bool $isObjectNew, string $sortingOrder): void
    {
        $this->attributeMock
            ->expects($this->exactly(2))
            ->method('getData')
            ->willReturnMap([
                ['used_in_product_listing', null, '1'],
                ['sorting_order', null, $sortingOrder]
            ]);

        $this->attributeMock
            ->expects($this->once())
            ->method('isObjectNew')
            ->with(null)
            ->willReturn($isObjectNew);

        $this->getMaxSortOrderMock->expects($this->never())->method('execute');
        $this->attributeMock->expects($this->never())->method('setData');
        $this->subject->execute($this->attributeMock);
    }

    /**
     * @param bool $isObjectNew
     * @param string|null $sortingOrder
     * @param int $maxSortOrder
     * @param int $expectedSortingOrder
     * @param int $expectedSortOrder
     * @return void
     * @dataProvider executeDataProvider
     */
    public function testExecute(
        bool $isObjectNew,
        ?string $sortingOrder,
        int $maxSortOrder,
        int $expectedSortingOrder,
        int $expectedSortOrder
    ): void {
        $this->attributeMock
            ->expects($this->exactly(2))
            ->method('getData')
            ->willReturnMap([
                ['used_in_product_listing', null, '1'],
                ['sorting_order', null, $sortingOrder]
            ]);

        $this->attributeMock
            ->expects($this->once())
            ->method('isObjectNew')
            ->with(null)
            ->willReturn($isObjectNew);

        $this->getMaxSortOrderMock
            ->expects($this->once())
            ->method('execute')
            ->willReturn($maxSortOrder);

        $this->attributeMock
            ->expects($this->exactly(2))
            ->method('setData')
            ->withConsecutive(
                ['sorting_order', $expectedSortingOrder],
                ['sort_order', $expectedSortOrder]
            );

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
            [true, null, 10, 20, 1020],
            [true, '', 10, 20, 1020],
            [true, '0', 10, 20, 1020],
            [false, null, 10, 20, 1020],
            [false, '', 10, 20, 1020]
        ];
    }
}
