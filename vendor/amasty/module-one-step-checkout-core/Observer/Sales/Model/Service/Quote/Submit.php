<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Observer\Sales\Model\Service\Quote;

use Amasty\CheckoutCore\Api\AccountManagementInterface;
use Amasty\CheckoutCore\Model\Config;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Psr\Log\LoggerInterface;

class Submit implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var AccountManagementInterface
     */
    private $accountManagement;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var IndexerRegistry
     */
    private $indexerRegistry;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        AccountManagementInterface $accountManagement,
        Session $customerSession,
        IndexerRegistry $indexerRegistry,
        Config $config,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory,
        LoggerInterface $logger
    ) {
        $this->accountManagement = $accountManagement;
        $this->customerSession = $customerSession;
        $this->indexerRegistry = $indexerRegistry;
        $this->config = $config;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getData('order');
        if ($observer->getException()) {
            $this->accountManagement->deletePassword($order);
        } else {
            try {
                /** @var \Magento\Customer\Model\Data\Customer|bool $account */
                $account = $this->accountManagement->createAccount($order);

                if ($account) {
                    $this->indexerRegistry->get(Customer::CUSTOMER_GRID_INDEXER_ID)->reindexRow($account->getId());

                    if ($this->config->getAdditionalOptions('automatically_login')) {
                        $this->login($account->getId());
                    }
                }
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage());
            }
        }
    }

    /**
     * @param int $accountId
     */
    private function login($accountId)
    {
        $this->customerSession->loginById($accountId);

        if ($this->cookieManager->getCookie('mage-cache-sessid')) {
            $metadata = $this->cookieMetadataFactory->createCookieMetadata();
            $metadata->setPath('/');
            $this->cookieManager->deleteCookie('mage-cache-sessid', $metadata);
        }
    }
}
