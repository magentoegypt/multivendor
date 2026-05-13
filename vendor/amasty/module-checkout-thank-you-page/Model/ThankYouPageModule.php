<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Thank you Page 2 for Magento 2 (System)
 */

namespace Amasty\CheckoutThankYouPage\Model;

use Magento\Framework\Module\Manager;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ThankYouPageModule
{
    public const MODULE_THANK_YOU_PAGE = 'Amasty_ThankYouPage';
    public const ENABLED_CONFIG_PATH = 'amasty_thank_you_page/general/enable';

    /**
     * @var Manager
     */
    private $moduleManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        Manager $moduleManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->moduleManager = $moduleManager;
        $this->scopeConfig = $scopeConfig;
    }

    public function isModuleEnable(string $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT, int $scopeId = 0): bool
    {
        return $this->moduleManager->isEnabled(self::MODULE_THANK_YOU_PAGE)
            && $this->scopeConfig->getValue(self::ENABLED_CONFIG_PATH, $scope, $scopeId);
    }
}
