<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Delivery Date for Magento 2 (System)
 */

namespace Amasty\CheckoutDeliveryDate\Test\Unit\Model;

use Amasty\CheckoutDeliveryDate\Model\DeliveryInformationManagement;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class DeliveryInformationManagementTest
 *
 * @see DeliveryInformationManagement
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class DeliveryInformationManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     *  @covers DeliveryInformationManagement::update
     */
    public function testUpdate()
    {
        $escaper = $this->createMock(\Magento\Framework\Escaper::class);
        $deliveryObject = $this->createMock(\Amasty\CheckoutDeliveryDate\Model\Delivery::class);
        $deliveryProvider = $this->createMock(\Amasty\CheckoutDeliveryDate\Model\DeliveryDateProvider::class);
        $deliveryResource = $this->createMock(\Amasty\CheckoutDeliveryDate\Model\ResourceModel\Delivery::class);
        $timezone = $this->createConfiguredMock(TimezoneInterface::class, ['getDateFormat' => 'd/M/yy']);

        $deliveryProvider->method('findByQuoteId')->willReturn($deliveryObject);
        $deliveryResource->method('save');
        $deliveryResource->method('delete');

        $model = new DeliveryInformationManagement(
            $deliveryResource,
            $deliveryProvider,
            $timezone,
            $escaper
        );

        $this->assertTrue($model->update(1, '15/10/2022', 1, 'test'));

        $deliveryObject->setId(5);
        $this->assertTrue($model->update(1, '', null, null));
    }
}
