<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model;

use Amasty\CheckoutCore\Api\Data\AdditionalFieldsInterface;
use Amasty\CheckoutCore\Model\AdditionalFieldsManagement;
use Amasty\CheckoutCore\Test\Unit\Traits;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @see AdditionalFieldsManagement
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class AdditionalFieldsManagementTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    private const FIELDS_DATA = [
        'id' => 2,
        'test_data' => 'test_data'
    ];

    /**
     * @var AdditionalFieldsManagement|MockObject
     */
    private $additionalFieldsManagement;

    /**
     * @var \Amasty\CheckoutCore\Model\AdditionalFields|MockObject
     */
    private $additionalFields;

    /**
     * @var \Amasty\CheckoutCore\Model\ResourceModel\AdditionalFields|MockObject
     */
    private $additionalFieldsResource;

    /**
     * @var \Amasty\CheckoutCore\Model\AdditionalFieldsFactory|MockObject
     */
    private $fieldsFactory;

    public function setUp(): void
    {
        $this->additionalFieldsManagement = $this->createPartialMock(
            AdditionalFieldsManagement::class,
            []
        );
        $this->additionalFields = $this->createPartialMock(
            \Amasty\CheckoutCore\Model\AdditionalFields::class,
            []
        );
        $this->additionalFieldsResource = $this->createMock(
            \Amasty\CheckoutCore\Model\ResourceModel\AdditionalFields::class
        );
        $this->fieldsFactory = $this->createMock(
            \Amasty\CheckoutCore\Model\AdditionalFieldsFactory::class
        );

        $this->additionalFieldsResource->expects($this->any())->method('save')
            ->willReturn(null);
        $this->fieldsFactory->expects($this->any())->method('create')
            ->willReturn($this->additionalFields);

        $this->setProperty(
            $this->additionalFieldsManagement,
            'fieldsResource',
            $this->additionalFieldsResource,
            AdditionalFieldsManagement::class
        );
        $this->setProperty(
            $this->additionalFieldsManagement,
            'fieldsFactory',
            $this->fieldsFactory,
            AdditionalFieldsManagement::class
        );


    }

    /**
     * @covers AdditionalFieldsManagement::save
     */
    public function testSave()
    {
        $cartId = 1;
        $this->setProperty(
            $this->additionalFieldsManagement,
            'storage',
            [1 => clone $this->additionalFields],
            AdditionalFieldsManagement::class
        );
        $this->additionalFields->setData(self::FIELDS_DATA);

        $result = $this->additionalFieldsManagement->save($cartId, $this->additionalFields);
        $this->assertTrue($result);
        $this->assertEquals($this->additionalFields->getData(), self::FIELDS_DATA);
    }

    /**
     * @covers AdditionalFieldsManagement::getByQuoteId
     */
    public function testGetByQuoteId()
    {
        $quoteId = 1;

        $this->additionalFieldsResource->expects($this->any())->method('load')
            ->with($this->additionalFields, $quoteId, AdditionalFieldsInterface::QUOTE_ID)
            ->willReturn(null);

        $result = $this->additionalFieldsManagement->getByQuoteId($quoteId);
        $this->assertEquals($result, $this->additionalFields);

        $result = $this->additionalFieldsManagement->getByQuoteId($quoteId);
        $this->assertEquals($result, $this->additionalFields);
    }

}
