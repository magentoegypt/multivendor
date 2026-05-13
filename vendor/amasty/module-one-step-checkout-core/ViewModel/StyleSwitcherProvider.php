<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\ViewModel;

use Amasty\CheckoutCore\Model\Config;
use Amasty\CheckoutCore\Model\Config\Source\Layout;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;

/**
 * Provider default style data.
 */
class StyleSwitcherProvider implements ArgumentInterface
{
    /**
     * @var Config
     */
    private $configProvider;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    public function __construct(
        Config $configProvider,
        CheckoutSession $checkoutSession
    ) {
        $this->configProvider = $configProvider;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return bool
     */
    public function isModernCheckoutDesign(): bool
    {
        return false;
    }

    /**
     * @return string
     */
    public function getLayoutTemplate(): string
    {
        return $this->configProvider->getLayoutTemplate($this->getStoreId());
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
