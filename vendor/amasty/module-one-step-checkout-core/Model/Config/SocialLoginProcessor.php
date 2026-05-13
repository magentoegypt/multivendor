<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Config;

use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutCore\Model\Config\SocialLogin\CheckoutPositionValue;
use Magento\Framework\App\Config\ReinitableConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;

class SocialLoginProcessor
{
    public const OSC_CONFIG_CHANGED = 'admin_system_config_changed_section_amasty_checkout';
    public const SL_CONFIG_CHANGED = 'admin_system_config_changed_section_amsociallogin';
    
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var WriterInterface
     */
    private $writer;
    
    /**
     * @var ReinitableConfigInterface
     */
    private $reinitableConfig;

    /**
     * @var CheckoutPositionValue
     */
    private $checkoutPositionValue;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        WriterInterface $writer,
        ReinitableConfigInterface $reinitableConfig,
        CheckoutPositionValue $checkoutPositionValue
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->writer = $writer;
        $this->reinitableConfig = $reinitableConfig;
        $this->checkoutPositionValue = $checkoutPositionValue;
    }

    /**
     * Synchronize checkout position in OSC and SocialLogin modules
     *
     * @param string $scope
     * @param int $scopeId
     * @param string $name
     * @return void
     */
    public function process(string $scope, int $scopeId, string $name): void
    {
        $socialLoginValue = $this->checkoutPositionValue->getPositionValue($scope, $scopeId);
        $checkoutValue = $this->getCheckoutValue($scope, $scopeId);
        if ($socialLoginValue !== $checkoutValue) {
            switch ($name) {
                case self::OSC_CONFIG_CHANGED:
                    $this->saveSocialLoginValue($checkoutValue, $scope, $scopeId);
                    break;
                case self::SL_CONFIG_CHANGED:
                    $this->saveCheckoutValue($socialLoginValue, $scope, $scopeId);
                    break;
            }
        }
    }
    
    private function saveSocialLoginValue(int $value, string $scope, int $scopeId): void
    {
        $socialLoginPositions = (string)$this->scopeConfig->getValue(
            Config::SOCIAL_LOGIN_POSITION_PATH,
            $scope,
            $scopeId
        );
        $socialLoginPositions = explode(',', $socialLoginPositions);
        $positionKey = array_search(Config::SOCIAL_LOGIN_CHECKOUT_PAGE_POSITION, $socialLoginPositions);

        if ($value == 1 && !$positionKey) {
            $socialLoginPositions[] = Config::SOCIAL_LOGIN_CHECKOUT_PAGE_POSITION;
        }

        if ($value == 0 && $positionKey) {
            unset($socialLoginPositions[$positionKey]);
        }

        $this->saveConfig(Config::SOCIAL_LOGIN_POSITION_PATH, implode(',', $socialLoginPositions), $scope, $scopeId);
    }

    private function saveCheckoutValue(int $value, string $scope, int $scopeId): void
    {
        $this->saveConfig(
            Config::PATH_PREFIX . Config::ADDITIONAL_OPTIONS . Config::FIELD_SOCIAL_LOGIN,
            (string)$value,
            $scope,
            $scopeId
        );
    }

    private function saveConfig(string $path, string $value, string $scope, int $scopeId): self
    {
        $this->writer->save($path, $value, $scope, $scopeId);
        $this->reinitableConfig->reinit();
        return $this;
    }

    private function getCheckoutValue(string $scope, int $scopeId): int
    {
        return (int)$this->scopeConfig->getValue(
            Config::PATH_PREFIX . Config::ADDITIONAL_OPTIONS . Config::FIELD_SOCIAL_LOGIN,
            $scope,
            $scopeId
        );
    }
}
