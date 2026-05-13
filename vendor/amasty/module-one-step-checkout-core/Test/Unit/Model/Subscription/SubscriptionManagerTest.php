<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model\Subscription;

use Amasty\Base\Model\MagentoVersion;
use Amasty\CheckoutCore\Model\Subscription\SubscriptionManager;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\ObjectManagerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @see SubscriptionManager
 */
class SubscriptionManagerTest extends TestCase
{
    private const CUSTOMER_EMAIL = 'test@test.com';
    private const STORE_ID = 1;
    private const CUSTOMER_ID = 1;
    private const OLD_MAGENTO_VERSION = '2.3.3';
    private const ACTUAL_MAGENTO_VERSION = '2.4.3';
    private const SUBSCRIPTION_ID = 1;
    private const STATUS_UNSUBSCRIBED = '3';
    private const STATUS_SUBSCRIBED = '1';

    /**
     * @var Customer|MockObject
     */
    private $customerMock;

    /**
     * @var CustomerSession|MockObject
     */
    private $customerSessionMock;

    /**
     * @var StoreInterface|MockObject
     */
    private $storeMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var ObjectManagerInterface|MockObject
     */
    private $objectManagerMock;

    /**
     * @var MagentoVersion|MockObject
     */
    private $magentoVersionMock;

    /**
     * @var Subscriber|MockObject
     */
    private $subscriberMock;

    /**
     * @var SubscriberFactory|MockObject
     */
    private $subscriberFactoryMock;

    /**
     * @var SubscriptionManagerInterface|MockObject
     */
    private $subscriptionManagerMock;

    /**
     * @var SubscriptionManager
     */
    private $subject;

    protected function setUp(): void
    {
        $this->customerMock = $this->createConfiguredMock(Customer::class, ['getId' => self::CUSTOMER_ID]);
        $this->customerSessionMock = $this->createConfiguredMock(
            CustomerSession::class,
            ['getCustomer' => $this->customerMock]
        );
        $this->storeMock = $this->createConfiguredMock(StoreInterface::class, ['getId' => self::STORE_ID]);
        $this->storeManagerMock = $this->createConfiguredMock(
            StoreManagerInterface::class,
            ['getStore' => $this->storeMock]
        );
        $this->objectManagerMock = $this->createMock(ObjectManagerInterface::class);
        $this->magentoVersionMock = $this->createMock(MagentoVersion::class);
        $this->subscriberMock = $this->createMock(Subscriber::class);
        $this->subscriberFactoryMock = $this->createConfiguredMock(
            SubscriberFactory::class,
            ['create' => $this->subscriberMock]
        );
        $this->subscriptionManagerMock = $this->createMock(SubscriptionManagerInterface::class);

        $this->subject = new SubscriptionManager(
            $this->customerSessionMock,
            $this->storeManagerMock,
            $this->objectManagerMock,
            $this->magentoVersionMock,
            $this->subscriberFactoryMock
        );
    }

    public function testSubscribeWithOldMagentoVersion(): void
    {
        $this->magentoVersionMock->expects($this->once())->method('get')->willReturn(self::OLD_MAGENTO_VERSION);
        $this->objectManagerMock->expects($this->never())->method('create');
        $this->subscriberMock->expects($this->once())->method('getId')->willReturn(self::SUBSCRIPTION_ID);
        $this->subscriberMock->expects($this->once())->method('getStatus')->willReturn(self::STATUS_UNSUBSCRIBED);
        $this->subscriberMock->expects($this->once())->method('subscribe')->willReturn(self::STATUS_SUBSCRIBED);

        $this->assertEquals(self::STATUS_SUBSCRIBED, $this->subject->subscribe(self::CUSTOMER_EMAIL));
    }

    public function testSubscribeWithLoggedCustomer()
    {
        $this->magentoVersionMock->expects($this->once())->method('get')->willReturn(self::ACTUAL_MAGENTO_VERSION);
        $this->objectManagerMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->subscriptionManagerMock);
        $this->customerSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(true);
        $this->subscriptionManagerMock
            ->expects($this->once())
            ->method('subscribeCustomer')
            ->willReturn($this->subscriberMock);
        $this->subscriberMock->expects($this->once())->method('__call')->willReturn(self::STATUS_SUBSCRIBED);

        $this->assertEquals(self::STATUS_SUBSCRIBED, $this->subject->subscribe(self::CUSTOMER_EMAIL));
    }

    public function testSubscribeWithNotLoggedCustomer()
    {
        $this->magentoVersionMock->expects($this->once())->method('get')->willReturn(self::ACTUAL_MAGENTO_VERSION);
        $this->objectManagerMock
            ->expects($this->once())
            ->method('create')
            ->willReturn($this->subscriptionManagerMock);
        $this->customerSessionMock->expects($this->once())->method('isLoggedIn')->willReturn(false);
        $this->subscriptionManagerMock->expects($this->once())->method('subscribe')->willReturn($this->subscriberMock);
        $this->subscriberMock->expects($this->once())->method('__call')->willReturn(self::STATUS_SUBSCRIBED);

        $this->assertEquals(self::STATUS_SUBSCRIBED, $this->subject->subscribe(self::CUSTOMER_EMAIL));
    }
}
