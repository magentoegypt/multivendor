<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model;

use Amasty\CheckoutCore\Model\Subscription\SubscriptionManager;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Validator\EmailAddress as EmailValidator;
use Magento\Newsletter\Model\Subscriber;
use Magento\Store\Model\StoreManagerInterface;

class Subscription
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var EmailValidator
     */
    private $emailValidator;

    /**
     * @var Config
     */
    private $configProvider;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var AccountManagementInterface
     */
    private $customerAccountManagement;

    /**
     * @var SubscriptionManager
     */
    private $subscriptionManager;

    public function __construct(
        ManagerInterface $messageManager,
        CheckoutSession $checkoutSession,
        EmailValidator $emailValidator,
        Config $configProvider,
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        AccountManagementInterface $customerAccountManagement,
        SubscriptionManager $subscriptionManager
    ) {
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->emailValidator = $emailValidator;
        $this->configProvider = $configProvider;
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->customerAccountManagement = $customerAccountManagement;
        $this->subscriptionManager = $subscriptionManager;
    }

    /**
     * @param string|null $email
     */
    public function subscribe($email = null)
    {
        $status = '';

        if ($email === null) {
            $email = $this->checkoutSession->getQuote()->getCustomerEmail();
        }

        try {
            if ($this->validateEmailFormat($email)
                && $this->validateGuestSubscription()
                && $this->validateEmailAvailable($email)
            ) {
                $status = $this->subscriptionManager->subscribe($email);
            }

            if ($status == Subscriber::STATUS_NOT_ACTIVE) {
                $this->messageManager->addSuccessMessage(__('The confirmation request has been sent.'));
            } elseif (!empty($status)) {
                $this->messageManager->addSuccessMessage(__('Thank you for your subscription.'));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('There was a problem with the subscription: %1', $e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong with the subscription.'));
        }
    }

    /**
     * @return bool
     */
    private function validateGuestSubscription()
    {
        return $this->configProvider->allowGuestSubscribe() || $this->customerSession->isLoggedIn();
    }

    /**
     * @param string $email
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function validateEmailAvailable($email)
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();

        return $this->customerSession->getCustomerDataObject()->getEmail() === $email
            || $this->customerAccountManagement->isEmailAvailable($email, $websiteId);
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    private function validateEmailFormat($email)
    {
        return $this->emailValidator->isValid($email);
    }
}
