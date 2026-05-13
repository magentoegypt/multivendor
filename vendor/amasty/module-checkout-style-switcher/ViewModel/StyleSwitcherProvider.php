<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Style Switcher for Magento 2 (System)
 */

namespace Amasty\CheckoutStyleSwitcher\ViewModel;

use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutCore\Model\Config\Source\Layout;
use Amasty\CheckoutStyleSwitcher\Model\ConfigProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;

class StyleSwitcherProvider implements ArgumentInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Config
     */
    private $oscConfigProvider;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    public function __construct(
        ConfigProvider $configProvider,
        Config $oscConfigProvider,
        CheckoutSession $checkoutSession
    ) {
        $this->configProvider = $configProvider;
        $this->oscConfigProvider = $oscConfigProvider;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return bool
     */
    public function isModernCheckoutDesign(): bool
    {
        return (bool)$this->configProvider->getCheckoutDesign($this->getStoreId());
    }

    /**
     * @return string
     */
    public function getLayoutTemplate(): string
    {
        if ($this->isModernCheckoutDesign()) {
            return $this->configProvider->getLayoutModernTemplate($this->getStoreId());
        }

        return $this->oscConfigProvider->getLayoutTemplate($this->getStoreId());
    }

    /**
     * @return string
     */
    public function getDesignLayout(): string
    {
        if (!$this->getQuote()->isVirtual()) {
            return $this->getLayoutTemplate();
        }

        return Layout::TWO_COLUMNS;
    }

    /**
     * @return int
     */
    private function getStoreId(): int
    {
        return (int)$this->getQuote()->getStoreId();
    }

    /**
     * @return CartInterface|Quote
     */
    private function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }
}
