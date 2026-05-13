<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Style Switcher for Magento 2 (System)
 */

namespace Amasty\CheckoutStyleSwitcher\Block\Onepage;

use Amasty\CheckoutCore\Block\Onepage\LayoutWalker;
use Amasty\CheckoutCore\Block\Onepage\LayoutWalkerFactory;
use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutStyleSwitcher\Model\Config\Source\PlaceButtonLayout;
use Amasty\CheckoutStyleSwitcher\Model\ConfigProvider;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

/**
 * Additional Layout processor with all private and dynamic data
 */
class PlaceOrderPositionProcessor implements LayoutProcessorInterface
{
    /**
     * @var Config
     */
    private $checkoutConfig;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var LayoutWalker
     */
    private $walker;

    /**
     * @var LayoutWalkerFactory
     */
    private $walkerFactory;

    public function __construct(
        Config $checkoutConfig,
        ConfigProvider $configProvider,
        LayoutWalkerFactory $walkerFactory
    ) {
        $this->checkoutConfig = $checkoutConfig;
        $this->configProvider = $configProvider;
        $this->walkerFactory = $walkerFactory;
    }

    public function process($jsLayout)
    {
        if (!$this->checkoutConfig->isEnabled()) {
            return $jsLayout;
        }
        $this->walker = $this->walkerFactory->create(['layoutArray' => $jsLayout]);

        $this->walker->setValue('{CHECKOUT}.config.additionalClasses', $this->getAdditionalCheckoutClasses());

        return $this->walker->getResult();
    }

    /**
     * @return string
     */
    private function getAdditionalCheckoutClasses(): string
    {
        $position = $this->configProvider->getPlaceOrderPosition();
        $frontClasses = '';
        switch ($position) {
            case PlaceButtonLayout::FIXED_TOP:
                $frontClasses .= ' am-submit-fixed -top';
                break;
            case PlaceButtonLayout::FIXED_BOTTOM:
                $frontClasses .= ' am-submit-fixed -bottom';
                break;
            case PlaceButtonLayout::SUMMARY:
                $frontClasses .= ' am-submit-summary';
                $this->walker->setValue(
                    '{SIDEBAR}.>>.place-button.component',
                    'Amasty_CheckoutStyleSwitcher/js/view/place-button'
                );
                break;
        }

        return $frontClasses;
    }
}
