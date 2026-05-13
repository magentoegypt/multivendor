<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Config\SocialLogin;

use Amasty\CheckoutCore\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CheckoutPositionValue
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }
    
    public function getPositionValue(string $scope, int $scopeId): int
    {
        $socialLoginPositions = (string)$this->scopeConfig->getValue(
            Config::SOCIAL_LOGIN_POSITION_PATH,
            $scope,
            $scopeId
        );
        $socialLoginPositions = explode(',', $socialLoginPositions);

        return (int)in_array(Config::SOCIAL_LOGIN_CHECKOUT_PAGE_POSITION, $socialLoginPositions);
    }
}
