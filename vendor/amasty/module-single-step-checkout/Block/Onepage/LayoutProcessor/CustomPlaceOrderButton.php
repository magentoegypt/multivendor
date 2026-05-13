<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Block\Onepage\LayoutProcessor;

use Amasty\Checkout\Model\Config as ConfigProvider;
use Amasty\CheckoutCore\Block\Onepage\LayoutWalker;
use Amasty\CheckoutCore\Block\Onepage\LayoutWalkerFactory;
use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutStyleSwitcher\Model\ConfigProvider as StyleSwitcherConfig;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

class CustomPlaceOrderButton implements LayoutProcessorInterface
{
    public const SUMMARY = 'summary';

    /**
     * @var ConfigProvider
     */
    private $config;

    /**
     * @var Config
     */
    private $checkoutConfig;

    /**
     * @var LayoutWalkerFactory
     */
    private $walkerFactory;

    /**
     * @var LayoutWalker
     */
    private $walker;

    /**
     * @var StyleSwitcherConfig
     */
    private $styleSwitcherConfig;

    public function __construct(
        ConfigProvider $config,
        Config $checkoutConfig,
        StyleSwitcherConfig $styleSwitcherConfig,
        LayoutWalkerFactory $walkerFactory
    ) {
        $this->config = $config;
        $this->checkoutConfig = $checkoutConfig;
        $this->styleSwitcherConfig = $styleSwitcherConfig;
        $this->walkerFactory = $walkerFactory;
    }
    /**
     * @param array $jsLayout
     *
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function process($jsLayout): array
    {
        if (!$this->checkoutConfig->isEnabled()) {
            return $jsLayout;
        }

        $customText = __('Place Order');
        if ($this->config->isCustomPlaceButtonText()) {
            $customText = $this->config->getPlaceButtonText();
        }

        $this->walker = $this->walkerFactory->create(['layoutArray' => $jsLayout]);

        $this->walker->setValue('{CHECKOUT}.customPlaceButtonText', $customText);

        if ($this->styleSwitcherConfig->getPlaceOrderPosition() === self::SUMMARY) {
            $this->walker->setValue('{SIDEBAR}.>>.place-button.customLabel', $customText);
        }

        return $this->walker->getResult();
    }
}
