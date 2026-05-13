<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Field;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\Field\SetAttributeFrontendLabel;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\FrontendLabel;
use Magento\Eav\Model\Entity\Attribute\FrontendLabelFactory;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see SetAttributeFrontendLabel
 * @covers SetAttributeFrontendLabel::execute
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class SetAttributeFrontendLabelTest extends \PHPUnit\Framework\TestCase
{
    private const DEFAULT_STORE_ID = Field::DEFAULT_STORE_ID;
    private const STORE_ID = 1;
    private const ANOTHER_STORE_ID = 2;
    private const LABEL = 'some label';
    private const ANOTHER_LABEL = 'another label';

    /**
     * @var FrontendLabel|MockObject
     */
    private $frontendLabelMock;

    /**
     * @var FrontendLabelFactory|MockObject
     */
    private $frontendLabelFactoryMock;

    /**
     * @var SetAttributeFrontendLabel
     */
    private $subject;

    protected function setUp(): void
    {
        $this->frontendLabelMock = $this->createMock(FrontendLabel::class);
        $this->frontendLabelFactoryMock = $this->createConfiguredMock(
            FrontendLabelFactory::class,
            ['create' => $this->frontendLabelMock]
        );

        $this->subject = new SetAttributeFrontendLabel($this->frontendLabelFactoryMock);
    }

    public function testExecuteWithDefaultStoreId(): void
    {
        $attributeMock = $this->createMock(Attribute::class);
        $attributeMock
            ->expects($this->once())
            ->method('setData')
            ->with(AttributeInterface::FRONTEND_LABEL, self::LABEL);

        $this->frontendLabelFactoryMock->expects($this->never())->method('create');
        $attributeMock->expects($this->never())->method('setFrontendLabels');
        $this->subject->execute($attributeMock, self::DEFAULT_STORE_ID, self::LABEL);
    }

    public function testExecuteWithStoreIdAndNoFrontendLabels(): void
    {
        $attributeMock = $this->createConfiguredMock(Attribute::class, [
            'getFrontendLabels' => [],
            'getStoreLabels'    => []
        ]);

        $this->frontendLabelMock
            ->expects($this->once())
            ->method('setStoreId')
            ->with(self::STORE_ID);
        $this->frontendLabelMock
            ->expects($this->once())
            ->method('setLabel')
            ->with(self::LABEL);

        $attributeMock
            ->expects($this->once())
            ->method('setFrontendLabels')
            ->with([$this->frontendLabelMock]);
        $attributeMock
            ->expects($this->once())
            ->method('setData')
            ->with('store_labels', [self::STORE_ID => self::LABEL]);

        $this->subject->execute($attributeMock, self::STORE_ID, self::LABEL);
    }

    public function testExecuteWithStoreIdAndExistingFrontendLabel(): void
    {
        $frontendLabel = $this->createConfiguredMock(FrontendLabel::class, [
            'getStoreId'    => self::STORE_ID,
            'getLabel'      => self::ANOTHER_LABEL
        ]);

        $attributeMock = $this->createConfiguredMock(Attribute::class, [
            'getFrontendLabels' => [$frontendLabel],
            'getStoreLabels'    => [self::STORE_ID => self::ANOTHER_LABEL]
        ]);

        $frontendLabel
            ->expects($this->once())
            ->method('setLabel')
            ->with(self::LABEL);

        $attributeMock
            ->expects($this->once())
            ->method('setData')
            ->with('store_labels', [self::STORE_ID => self::LABEL]);

        $attributeMock->expects($this->never())->method('setFrontendLabels');
        $this->frontendLabelFactoryMock->expects($this->never())->method('create');
        $this->subject->execute($attributeMock, self::STORE_ID, self::LABEL);
    }

    public function testExecuteWithStoreIdAndMissingFrontendLabel(): void
    {
        $frontendLabel = $this->createConfiguredMock(FrontendLabel::class, [
            'getStoreId'    => self::ANOTHER_STORE_ID,
            'getLabel'      => self::ANOTHER_LABEL
        ]);

        $attributeMock = $this->createConfiguredMock(Attribute::class, [
            'getFrontendLabels' => [$frontendLabel],
            'getStoreLabels'    => [self::ANOTHER_STORE_ID => self::ANOTHER_LABEL]
        ]);

        $this->frontendLabelMock
            ->expects($this->once())
            ->method('setStoreId')
            ->with(self::STORE_ID);
        $this->frontendLabelMock
            ->expects($this->once())
            ->method('setLabel')
            ->with(self::LABEL);

        $attributeMock
            ->expects($this->once())
            ->method('setFrontendLabels')
            ->with([$frontendLabel, $this->frontendLabelMock]);

        $attributeMock
            ->expects($this->once())
            ->method('setData')
            ->with(
                'store_labels',
                [self::ANOTHER_STORE_ID => self::ANOTHER_LABEL, self::STORE_ID => self::LABEL]
            );

        $frontendLabel->expects($this->never())->method('setLabel');
        $frontendLabel->expects($this->never())->method('setStoreId');
        $this->subject->execute($attributeMock, self::STORE_ID, self::LABEL);
    }
}
