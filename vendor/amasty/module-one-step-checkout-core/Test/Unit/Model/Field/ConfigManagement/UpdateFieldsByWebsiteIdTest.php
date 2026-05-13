<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field\ConfigManagement;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\UpdateFieldsByWebsiteId;
use Amasty\CheckoutCore\Model\Field\DuplicateField;
use Amasty\CheckoutCore\Model\Field\GetDefaultField;
use Amasty\CheckoutCore\Model\ResourceModel\Field as FieldResource;
use Amasty\CheckoutCore\Model\ResourceModel\Field\Collection;
use Amasty\CheckoutCore\Model\ResourceModel\Field\Collection\FilterByAttributeAndStore;
use Amasty\CheckoutCore\Model\ResourceModel\Field\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\Website;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see UpdateFieldsByWebsiteId
 * @covers UpdateFieldsByWebsiteId::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class UpdateFieldsByWebsiteIdTest extends \PHPUnit\Framework\TestCase
{
    private const ATTRIBUTE_ID = 1;
    private const WEBSITE_ID = 1;

    /**
     * @var WebsiteRepositoryInterface|MockObject
     */
    private $websiteRepositoryMock;

    /**
     * @var DuplicateField|MockObject
     */
    private $duplicateFieldMock;

    /**
     * @var Collection|MockObject
     */
    private $collectionMock;

    /**
     * @var GetDefaultField|MockObject
     */
    private $getDefaultFieldMock;

    /**
     * @var FieldResource|MockObject
     */
    private $fieldResourceMock;

    /**
     * @var UpdateFieldsByWebsiteId
     */
    private $subject;

    protected function setUp(): void
    {
        $this->websiteRepositoryMock = $this->createMock(WebsiteRepositoryInterface::class);
        $this->duplicateFieldMock = $this->createMock(DuplicateField::class);
        $this->collectionMock = $this->createMock(Collection::class);
        $collectionFactoryMock = $this->createConfiguredMock(
            CollectionFactory::class,
            ['create' => $this->collectionMock]
        );

        $filterByAttributeAndStoreMock = $this->createMock(FilterByAttributeAndStore::class);
        $this->getDefaultFieldMock = $this->createMock(GetDefaultField::class);
        $this->fieldResourceMock = $this->createMock(FieldResource::class);

        $this->subject = new UpdateFieldsByWebsiteId(
            $this->websiteRepositoryMock,
            $this->duplicateFieldMock,
            $collectionFactoryMock,
            $filterByAttributeAndStoreMock,
            $this->getDefaultFieldMock,
            $this->fieldResourceMock
        );
    }

    /**
     * @param bool $isEnabled
     * @param bool $isRequired
     * @dataProvider executeDataProvider
     */
    public function testExecuteWithoutWebsite(bool $isEnabled, bool $isRequired): void
    {
        $message = 'test';
        $this->expectException(NoSuchEntityException::class);
        $this->expectExceptionMessage($message);

        $this->websiteRepositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with(self::WEBSITE_ID)
            ->willThrowException(new NoSuchEntityException(new Phrase($message)));

        $this->collectionMock->expects($this->never())->method('getItemByColumnValue');
        $this->getDefaultFieldMock->expects($this->never())->method('execute');
        $this->duplicateFieldMock->expects($this->never())->method('execute');
        $this->fieldResourceMock->expects($this->never())->method('save');
        $this->subject->execute(self::ATTRIBUTE_ID, self::WEBSITE_ID, $isEnabled, $isRequired);
    }

    /**
     * @param bool $isEnabled
     * @param bool $isRequired
     * @dataProvider executeDataProvider
     */
    public function testExecuteWithExistingField(bool $isEnabled, bool $isRequired): void
    {
        $storeId = '1';
        $this->mockWebsite([$storeId]);

        $fieldMock = $this->createMock(Field::class);
        $fieldMock->expects($this->once())->method('setIsEnabled')->with($isEnabled);
        $fieldMock->expects($this->once())->method('setIsRequired')->with($isRequired);

        $defaultFieldMock = $this->createMock(Field::class);
        $defaultFieldMock->expects($this->never())->method('setIsEnabled');
        $defaultFieldMock->expects($this->never())->method('setIsRequired');

        $this->getDefaultFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_ID)
            ->willReturn($defaultFieldMock);

        $this->collectionMock
            ->expects($this->once())
            ->method('getItemByColumnValue')
            ->with(Field::STORE_ID, $storeId)
            ->willReturn($fieldMock);

        $this->fieldResourceMock
            ->expects($this->once())
            ->method('save')
            ->with($fieldMock);

        $this->subject->execute(self::ATTRIBUTE_ID, self::WEBSITE_ID, $isEnabled, $isRequired);
    }

    /**
     * @param bool $isEnabled
     * @param bool $isRequired
     * @dataProvider executeDataProvider
     */
    public function testExecuteWithMultipleFields(bool $isEnabled, bool $isRequired): void
    {
        $this->mockWebsite(['1', '2']);

        $fieldAMock = $this->createMock(Field::class);
        $fieldAMock->expects($this->once())->method('setIsEnabled')->with($isEnabled);
        $fieldAMock->expects($this->once())->method('setIsRequired')->with($isRequired);

        $fieldBMock = $this->createMock(Field::class);
        $fieldBMock->expects($this->once())->method('setIsEnabled')->with($isEnabled);
        $fieldBMock->expects($this->once())->method('setIsRequired')->with($isRequired);

        $defaultFieldMock = $this->createMock(Field::class);
        $defaultFieldMock->expects($this->never())->method('setIsEnabled');
        $defaultFieldMock->expects($this->never())->method('setIsRequired');

        $this->getDefaultFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_ID)
            ->willReturn($defaultFieldMock);

        $this->collectionMock
            ->expects($this->exactly(2))
            ->method('getItemByColumnValue')
            ->willReturnMap([
                [Field::STORE_ID, '1', $fieldAMock],
                [Field::STORE_ID, '2', $fieldBMock]
            ]);

        $this->fieldResourceMock
            ->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive([$fieldAMock], [$fieldBMock]);

        $this->subject->execute(self::ATTRIBUTE_ID, self::WEBSITE_ID, $isEnabled, $isRequired);
    }

    /**
     * @param bool $isEnabled
     * @param bool $isRequired
     * @dataProvider executeDataProvider
     */
    public function testExecuteWithMissingField(bool $isEnabled, bool $isRequired): void
    {
        $storeId = '1';
        $this->mockWebsite([$storeId]);

        $defaultFieldMock = $this->createMock(Field::class);
        $defaultFieldMock->expects($this->never())->method('setIsEnabled');
        $defaultFieldMock->expects($this->never())->method('setIsRequired');

        $duplicatedFieldMock = $this->createMock(Field::class);
        $duplicatedFieldMock->expects($this->once())->method('setStoreId')->with((int) $storeId);
        $duplicatedFieldMock->expects($this->once())->method('setIsEnabled')->with($isEnabled);
        $duplicatedFieldMock->expects($this->once())->method('setIsRequired')->with($isRequired);

        $this->getDefaultFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_ID)
            ->willReturn($defaultFieldMock);

        $this->collectionMock
            ->expects($this->once())
            ->method('getItemByColumnValue')
            ->with(Field::STORE_ID, $storeId)
            ->willReturn(null);

        $this->duplicateFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with($defaultFieldMock)
            ->willReturn($duplicatedFieldMock);

        $this->fieldResourceMock
            ->expects($this->once())
            ->method('save')
            ->with($duplicatedFieldMock);

        $this->subject->execute(self::ATTRIBUTE_ID, self::WEBSITE_ID, $isEnabled, $isRequired);
    }

    /**
     * @param bool $isEnabled
     * @param bool $isRequired
     * @dataProvider executeDataProvider
     */
    public function testExecuteWithMultipleMissingFields(bool $isEnabled, bool $isRequired): void
    {
        $this->mockWebsite(['1', '2']);

        $defaultFieldMock = $this->createMock(Field::class);
        $defaultFieldMock->expects($this->never())->method('setIsEnabled');
        $defaultFieldMock->expects($this->never())->method('setIsRequired');

        $duplicatedFieldAMock = $this->createMock(Field::class);
        $duplicatedFieldBMock = $this->createMock(Field::class);

        $this->getDefaultFieldMock
            ->expects($this->once())
            ->method('execute')
            ->with(self::ATTRIBUTE_ID)
            ->willReturn($defaultFieldMock);

        $this->collectionMock
            ->expects($this->exactly(2))
            ->method('getItemByColumnValue')
            ->willReturnMap([
                [Field::STORE_ID, '1', null],
                [Field::STORE_ID, '2', null]
            ]);

        $duplicatedFieldBMock = $this->createMock(Field::class);
        $this->duplicateFieldMock
            ->expects($this->exactly(2))
            ->method('execute')
            ->with($defaultFieldMock)
            ->willReturnOnConsecutiveCalls(
                $duplicatedFieldAMock,
                $duplicatedFieldBMock
            );

        $duplicatedFieldAMock->expects($this->once())->method('setStoreId')->with(1);
        $duplicatedFieldAMock->expects($this->once())->method('setIsEnabled')->with($isEnabled);
        $duplicatedFieldAMock->expects($this->once())->method('setIsRequired')->with($isRequired);
        $duplicatedFieldBMock->expects($this->once())->method('setStoreId')->with(2);
        $duplicatedFieldBMock->expects($this->once())->method('setIsEnabled')->with($isEnabled);
        $duplicatedFieldBMock->expects($this->once())->method('setIsRequired')->with($isRequired);

        $this->fieldResourceMock
            ->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive([$duplicatedFieldAMock], [$duplicatedFieldBMock]);

        $this->subject->execute(self::ATTRIBUTE_ID, self::WEBSITE_ID, $isEnabled, $isRequired);
    }

    public function executeDataProvider(): array
    {
        return [
            [false, false],
            [false, true],
            [true, false],
            [true, true]
        ];
    }

    /**
     * @param string[] $storeIds
     */
    private function mockWebsite(array $storeIds): void
    {
        $websiteMock = $this->createMock(Website::class);
        $websiteMock
            ->expects($this->once())
            ->method('getStoreIds')
            ->willReturn($storeIds);

        $this->websiteRepositoryMock
            ->expects($this->once())
            ->method('getById')
            ->with(self::WEBSITE_ID)
            ->willReturn($websiteMock);
    }
}
