<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const XML_PATH_ENABLED              = 'pushnotification/fcm/enabled';
    const XML_PATH_PROJECT_ID           = 'pushnotification/fcm/project_id';
    const XML_PATH_SERVICE_ACCOUNT_PATH = 'pushnotification/fcm/service_account_path';
    const XML_PATH_BATCH_SIZE           = 'pushnotification/fcm/batch_size';
    const XML_PATH_TIMEOUT              = 'pushnotification/fcm/timeout';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function isEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    public function getProjectId(): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_PROJECT_ID, ScopeInterface::SCOPE_STORE);
    }

    public function getServiceAccountPath(): string
    {
        return (string) $this->scopeConfig->getValue(self::XML_PATH_SERVICE_ACCOUNT_PATH, ScopeInterface::SCOPE_STORE);
    }

    public function getBatchSize(): int
    {
        $value = (int) $this->scopeConfig->getValue(self::XML_PATH_BATCH_SIZE, ScopeInterface::SCOPE_STORE);
        return $value > 0 ? $value : 500;
    }

    public function getTimeout(): int
    {
        $value = (int) $this->scopeConfig->getValue(self::XML_PATH_TIMEOUT, ScopeInterface::SCOPE_STORE);
        return $value > 0 ? $value : 15;
    }
}
