<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Subscription;

use Amasty\Base\Model\MagentoVersion;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\ObjectManagerInterface;
use Magento\Newsletter\Model\Subscriber;
use Magento\Newsletter\Model\SubscriberFactory;
use Magento\Newsletter\Model\SubscriptionManagerInterface;
use Magento\Store\Model\StoreManagerInterface;

class SubscriptionManager
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var MagentoVersion
     */
    private $magentoVersion;

    /**
     * @var SubscriberFactory
     */
    private $subscriberFactory;

    public function __construct(
        CustomerSession $customerSession,
        StoreManagerInterface $storeManager,
        ObjectManagerInterface $objectManager,
        MagentoVersion $magentoVersion,
        SubscriberFactory $subscriberFactory
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->objectManager = $objectManager;
        $this->magentoVersion = $magentoVersion;
        $this->subscriberFactory = $subscriberFactory;
    }

    public function subscribe(string $email): string
    {
        $status = '';

        if (version_compare($this->magentoVersion->get(), '2.4.0', '>=')) {
            //for compatibility with m2.3 we use objectManager
            $subscribeModel = $this->objectManager->create(SubscriptionManagerInterface::class);

            $storeId = (int)$this->storeManager->getStore()->getId();

            if ($this->customerSession->isLoggedIn()) {
                $customer = $this->customerSession->getCustomer();
                $subscriber = $subscribeModel->subscribeCustomer((int)$customer->getId(), $storeId);
            } else {
                $subscriber = $subscribeModel->subscribe($email, $storeId);
            }

            $status = (string)$subscriber->getSubscriberStatus();
        } else {
            $subscriber = $this->subscriberFactory->create();
            $subscriber->loadByEmail($email);

            if (!$subscriber->getId() || (int)$subscriber->getStatus() === Subscriber::STATUS_UNSUBSCRIBED) {
                $status = (string)$this->subscriberFactory->create()->subscribe($email);
            }
        }

        return $status;
    }
}
