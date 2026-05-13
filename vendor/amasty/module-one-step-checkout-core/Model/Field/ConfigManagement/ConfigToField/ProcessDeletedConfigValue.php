<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToField;

use Amasty\CheckoutCore\Model\Field\ConfigManagement\ConfigToAttribute\UpdateAttributeFromConfig;
use Amasty\CheckoutCore\Model\Field\ConfigManagement\GetDefaultConfigValue;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class ProcessDeletedConfigValue
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var GetDefaultConfigValue
     */
    private $getDefaultConfigValue;

    /**
     * @var ProcessConfigValue
     */
    private $processConfigValue;

    /**
     * @var UpdateAttributeFromConfig
     */
    private $updateAttributeFromConfig;

    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        GetDefaultConfigValue $getDefaultConfigValue,
        ProcessConfigValue $processConfigValue,
        UpdateAttributeFromConfig $updateAttributeFromConfig,
        ReinitableConfigInterface $reinitableConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->getDefaultConfigValue = $getDefaultConfigValue;
        $this->processConfigValue = $processConfigValue;
        $this->updateAttributeFromConfig = $updateAttributeFromConfig;
        $this->reinitableConfig = $reinitableConfig;
    }

    /**
     * @param Value $configValue
     * @return void
     * @throws AlreadyExistsException
     * @throws NoSuchEntityException
     */
    public function execute(Value $configValue): void
    {
        $scope = $configValue->getScope();

        if ($scope === ScopeInterface::SCOPE_STORES) {
            return;
        }

        if ($scope === ScopeConfigInterface::SCOPE_TYPE_DEFAULT) {
            $value = $this->getDefaultConfigValue->execute($configValue->getPath());
            if ($value !== null) {
                $this->processConfigValue->execute($configValue, $value, null);
                $this->updateAttributeFromConfig->execute(
                    $configValue,
                    $value,
                    UpdateAttributeFromConfig::DEFAULT_WEBSITE_ID
                );
            }

            return;
        }

        $this->reinitableConfig->reinit();

        $value = $this->getDefaultValueForWebsite($configValue);
        if ($value !== null) {
            $websiteId = (int) $configValue->getScopeId();
            $this->processConfigValue->execute($configValue, $value, $websiteId);
            $this->updateAttributeFromConfig->execute($configValue, $value, $websiteId);
        }
    }

    private function getDefaultValueForWebsite(Value $configValue): ?string
    {
        $configPath = $configValue->getPath();

        $value = $this->scopeConfig->getValue(
            $configPath,
            ScopeConfigInterface::SCOPE_TYPE_DEFAULT,
            Store::DEFAULT_STORE_ID
        );

        return $value !== null ? $value : $this->getDefaultConfigValue->execute($configPath);
    }
}
