<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Observer\System;

use Amasty\CheckoutCore\Model\Config\SocialLogin\DeleteConfigProcessor;
use Amasty\CheckoutCore\Model\Config\SocialLoginProcessor;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigChanged implements ObserverInterface
{
    /**
     * @var SocialLoginProcessor
     */
    private $socialLoginProcessor;

    /**
     * @var DeleteConfigProcessor
     */
    private $deleteConfigProcessor;

    public function __construct(
        SocialLoginProcessor $socialLoginProcessor,
        DeleteConfigProcessor $deleteConfigProcessor
    ) {
        $this->socialLoginProcessor = $socialLoginProcessor;
        $this->deleteConfigProcessor = $deleteConfigProcessor;
    }

    public function execute(Observer $observer): void
    {
        $scope = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeId = 0;
        if ($observer->getWebsite() !== '') {
            $scope = ScopeInterface::SCOPE_WEBSITES;
            $scopeId = (int)$observer->getWebsite();
        }
        if ($observer->getStore() !== '') {
            $scope = ScopeInterface::SCOPE_STORES;
            $scopeId = (int)$observer->getStore();
        }
        $name = $observer->getEvent()->getName();

        if ($scope !== ScopeConfigInterface::SCOPE_TYPE_DEFAULT && $name === SocialLoginProcessor::SL_CONFIG_CHANGED) {
            $this->deleteConfigProcessor->process($scope, $scopeId);
        }

        $this->socialLoginProcessor->process($scope, $scopeId, $name);
    }
}
