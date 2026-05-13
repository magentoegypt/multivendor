<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Test\Unit\Model;

use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutCore\Model\Subscription;
use Amasty\CheckoutCore\Model\Subscription\SubscriptionManager;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Data\Customer;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Quote\Model\Quote;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @see Subscription
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class SubscriptionTest extends TestCase
{
    private const CUSTOMER_EMAIL = 'test@test.com';
    private const INVALID_EMAIL = 'aaaaa';
    private const STATUS_SUBSCRIBED = '1';
    private const WEBSITE_ID = 1;

    /**
     * @var ManagerInterface|MockObject
     */
    private $messageManagerMock;

    /**
     * @var CheckoutSession|MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var EmailValidator|MockObject
     */
    private $emailValidatorMock;

    /**
     * @var Config|MockObject
     */
    private $configProviderMock;

    /**
     * @var CustomerSession|MockObject
     */
    private $customerSessionMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var AccountManagementInterface|MockObject
     */
    private $customerAccountManagementMock;

    /**
     * @var SubscriptionManager|MockObject
     */
    private $subscriptionManagerMock;

    /**
     * @var Customer|MockObject
     */
    private $customerDataObjectMock;

    /**
     * @var StoreInterface|MockObject
     */
    private $storeMock;

    /**
     * @var Subscription
     */
    private $subject;

    /**
     * @var Quote|MockObject
     */
    private $quoteMock;

    protected function setUp(): void
    {
        $this->messageManagerMock = $this->createMock(ManagerInterface::class);
        $this->checkoutSessionMock = $this->createMock(CheckoutSession::class);
        $this->emailValidatorMock = $this->createMock(EmailValidator::class);
        $this->configProviderMock = $this->createConfiguredMock(Config::class, ['allowGuestSubscribe' => true]);
        $this->storeMock = $this->createConfiguredMock(StoreInterface::class, ['getWebsiteId' => self::WEBSITE_ID]);
        $this->storeManagerMock = $this->createConfiguredMock(
            StoreManagerInterface::class,
            ['getStore' => $this->storeMock]
        );
        $this->customerAccountManagementMock = $this->createMock(AccountManagementInterface::class);
        $this->subscriptionManagerMock = $this->createMock(SubscriptionManager::class);
        $this->quoteMock = $this->createMock(Quote::class);
        $this->customerDataObjectMock = $this->createConfiguredMock(
            Customer::class,
            ['getEmail' => self::CUSTOMER_EMAIL]
        );
        $this->customerSessionMock = $this->createConfiguredMock(
            CustomerSession::class,
            ['getCustomerDataObject' => $this->customerDataObjectMock]
        );

        $this->subject = new Subscription (
            $this->messageManagerMock,
            $this->checkoutSessionMock,
            $this->emailValidatorMock,
            $this->configProviderMock,
            $this->customerSessionMock,
            $this->storeManagerMock,
            $this->customerAccountManagementMock,
            $this->subscriptionManagerMock
        );
    }

    /**
     * @covers Subscription::subscribe
     *
     * @return void
     */
    public function testSubscribeWithValidEmail(): void
    {
        $this->messageManagerMock->expects($this->never())->method('addExceptionMessage');
        $this->checkoutSessionMock->expects($this->never())->method('getQuote');
        $this->emailValidatorMock
            ->expects($this->once())
            ->method('isValid')
            ->with(self::CUSTOMER_EMAIL)
            ->willReturn(true);
        $this->subscriptionManagerMock
            ->expects($this->once())
            ->method('subscribe')
            ->willReturn(self::STATUS_SUBSCRIBED);
        $this->messageManagerMock->expects($this->once())->method('addSuccessMessage');

        $this->subject->subscribe(self::CUSTOMER_EMAIL);
    }

    /**
     * @covers Subscription::subscribe
     *
     * @return void
     */
    public function testSubscribeWithEmptyEmail(): void
    {
        $this->messageManagerMock->expects($this->never())->method('addExceptionMessage');
        $this->checkoutSessionMock->expects($this->once())->method('getQuote')->willReturn($this->quoteMock);
        $this->quoteMock->expects($this->once())->method('__call')->willReturn(self::CUSTOMER_EMAIL);
        $this->emailValidatorMock
            ->expects($this->once())
            ->method('isValid')
            ->with(self::CUSTOMER_EMAIL)
            ->willReturn(true);
        $this->subscriptionManagerMock
            ->expects($this->once())
            ->method('subscribe')
            ->willReturn(self::STATUS_SUBSCRIBED);
        $this->messageManagerMock->expects($this->once())->method('addSuccessMessage');

        $this->subject->subscribe();
    }

    /**
     * @covers Subscription::subscribe
     *
     * @return void
     */
    public function testSubscribeWithInvalidEmail(): void
    {
        $this->messageManagerMock->expects($this->never())->method('addExceptionMessage');
        $this->emailValidatorMock
            ->expects($this->once())
            ->method('isValid')
            ->with(self::INVALID_EMAIL)
            ->willReturn(false);
        $this->subscriptionManagerMock->expects($this->never())->method('subscribe');
        $this->messageManagerMock->expects($this->never())->method('addSuccessMessage');

        $this->subject->subscribe(self::INVALID_EMAIL);
    }
}
