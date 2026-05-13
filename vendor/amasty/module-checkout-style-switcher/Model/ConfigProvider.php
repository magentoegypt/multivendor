<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Style Switcher for Magento 2 (System)
 */

namespace Amasty\CheckoutStyleSwitcher\Model;

use Amasty\Base\Model\ConfigProviderAbstract;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider extends ConfigProviderAbstract
{
    /**
     * Path Prefix For Config
     */
    public const PATH_PREFIX = 'amasty_checkout/';

    public const DESIGN_BLOCK = 'design/';

    public const FIELD_CHECKOUT_DESIGN = 'checkout_design';
    public const FIELD_CHECKOUT_LAYOUT_MODERN = 'layout_modern';
    public const PLACE_BUTTON_LAYOUT = 'place_button_layout';

    public const BILLING_ADDRESS_ON_PAYMENT_METHOD = 0;
    public const BILLING_ADDRESS_ON_PAYMENT_PAGE = 1;
    public const BILLING_ADDRESS_BELOW_SHIPPING_ADDRESS = 2;

    /**
     * xpath prefix of module (section)
     *
     * @var string
     */
    protected $pathPrefix = self::PATH_PREFIX;

    /**
     * @param ?int $store
     * @return string
     */
    public function getCheckoutDesign(int $store = null): string
    {
        return (string)$this->getValue(self::DESIGN_BLOCK . self::FIELD_CHECKOUT_DESIGN, $store);
    }

    /**
     * @param ?int $store
     * @return string
     */
    public function getLayoutModernTemplate(int $store = null): string
    {
        return (string)$this->getValue(self::DESIGN_BLOCK . self::FIELD_CHECKOUT_LAYOUT_MODERN, $store);
    }

    /**
     * @param ?int $store
     * @return string
     */
    public function getPlaceOrderPosition(int $store = null): string
    {
        return (string)$this->getValue(self::DESIGN_BLOCK . self::PLACE_BUTTON_LAYOUT, $store);
    }

    /**
     * @param ?int $store
     * @return int
     */
    public function getBillingAddressDisplayOn(int $store = null): int
    {
        return (int)$this->scopeConfig->getValue(
            'checkout/options/display_billing_address_on',
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
