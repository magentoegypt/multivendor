<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Block\Onepage\LayoutProcessor;

use Amasty\Checkout\Model\BillingAddress;
use Amasty\Checkout\Model\Config as ConfigProvider;
use Amasty\CheckoutCore\Block\Onepage\LayoutWalker;
use Amasty\CheckoutCore\Block\Onepage\LayoutWalkerFactory;
use Amasty\CheckoutCore\Model\Config;
use Magento\Checkout\Block\Checkout\LayoutProcessorInterface;

class TooltipsProcessor implements LayoutProcessorInterface
{
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
     * @var BillingAddress
     */
    private $billingAddress;

    public function __construct(
        ConfigProvider $config,
        Config $checkoutConfig,
        BillingAddress $billingAddress,
        LayoutWalkerFactory $walkerFactory
    ) {
        $this->config = $config;
        $this->checkoutConfig = $checkoutConfig;
        $this->billingAddress = $billingAddress;
        $this->walkerFactory = $walkerFactory;
    }

    /**
     * @param array $jsLayout
     *
     * @return array
     */
    public function process($jsLayout): array
    {
        if (!$this->checkoutConfig->isEnabled()) {
            return $jsLayout;
        }

        $emailTooltip = $this->getTooltip('email');
        $phoneTooltip = $this->getTooltip('phone');

        $this->walker = $this->walkerFactory->create(['layoutArray' => $jsLayout]);

        $billingAddressPath = $this->billingAddress->getBillingPath($this->walker);

        if ($phoneTooltip) {
            $this->walker->setValue(
                '{SHIPPING_ADDRESS_FIELDSET}.>>.telephone.config.tooltip.description',
                $phoneTooltip
            );
            
            foreach ($billingAddressPath as $path) {
                $this->walker->setValue($path . '.telephone.config.tooltip.description', $phoneTooltip);
            }
        } else {
            $this->walker->unsetByPath('{SHIPPING_ADDRESS_FIELDSET}.>>.telephone.config.tooltip');
            
            foreach ($billingAddressPath as $path) {
                $this->walker->unsetByPath($path . '.telephone.config.tooltip');
            }
        }

        if ($emailTooltip) {
            $this->walker->setValue(
                '{SHIPPING_ADDRESS}.>>.customer-email.tooltip.description',
                $emailTooltip
            );
            $this->walker->setValue('{PAYMENT}.>>.customer-email.tooltip.description', $emailTooltip);
        } else {
            $this->walker->unsetByPath('{SHIPPING_ADDRESS}.>>.customer-email.tooltip');
            $this->walker->unsetByPath('{PAYMENT}.>>.customer-email.tooltip');
        }

        return $this->walker->getResult();
    }

    private function getTooltip(string $key): ?string
    {
        return $this->config->isTooltipEnable($key) ? $this->config->getTooltipText($key) : '';
    }
}
