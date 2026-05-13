<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field;

use Amasty\CheckoutCore\Model\Field\IsCustomFieldAttribute;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see IsCustomFieldAttribute
 * @covers IsCustomFieldAttribute::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class IsCustomFieldAttributeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Collection|MockObject
     */
    private $collectionMock;

    /**
     * @var IsCustomFieldAttribute
     */
    private $subject;

    protected function setUp(): void
    {
        $this->collectionMock = $this->createMock(Collection::class);
        $collectionFactoryMock = $this->createConfiguredMock(
            CollectionFactory::class,
            ['create' => $this->collectionMock]
        );

        $this->subject = new IsCustomFieldAttribute($collectionFactoryMock);
    }

    /**
     * @param int|null $attributeId
     * @dataProvider emptyAttributeIdDataProvider
     */
    public function testExecuteWithEmptyAttributeId(?int $attributeId): void
    {
        $this->assertFalse($this->subject->execute($attributeId));
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testExecuteAttributeNotFound(array $attributeMocks): void
    {
        $this->collectionMock->expects($this->once())->method('getAllIds')->willReturn($attributeMocks);
        $this->assertFalse($this->subject->execute(2));
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testExecuteAttributeFound(array $attributeMocks): void
    {
        $this->collectionMock->expects($this->once())->method('getAllIds')->willReturn($attributeMocks);
        $this->assertTrue($this->subject->execute(1));
    }

    /**
     * @dataProvider attributesDataProvider
     */
    public function testExecuteAttributeFoundWithCache(array $attributeMocks): void
    {
        $this->collectionMock->expects($this->once())->method('getAllIds')->willReturn($attributeMocks);
        $this->assertTrue($this->subject->execute(1));
        $this->assertTrue($this->subject->execute(1));
    }

    public function emptyAttributeIdDataProvider(): array
    {
        return [
            [0],
            [null]
        ];
    }

    public function attributesDataProvider(): array
    {
        return [
            [['1']],
            [[1]]
        ];
    }
}
