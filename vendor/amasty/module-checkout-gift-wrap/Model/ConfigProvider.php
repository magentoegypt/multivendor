<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) 2023 Amasty (https://www.amasty.com)
 * @package One Step Checkout Gift Wrap for Magento 2 (System)
 */

namespace Amasty\CheckoutGiftWrap\Model;

use Amasty\Base\Model\ConfigProviderAbstract;
use Magento\Store\Model\ScopeInterface;

class ConfigProvider extends ConfigProviderAbstract
{
    /**
     * Path Prefix For Config
     */
    public const PATH_PREFIX = 'amasty_checkout/';

    /**
     * Gift Wrap Config Prefix
     */
    public const GIFT_WRAP_PREFIX = 'amgiftwrap/';

    public const GENERAL_BLOCK = 'general/';
    public const GIFTS = 'gifts/';

    public const GIFT_WRAP = 'gift_wrap';
    public const GIFT_WRAP_FEE = 'gift_wrap_fee';
    public const GIFT_WRAP_MODULE = 'enabled';

    /**
     * xpath prefix of module (section)
     *
     * @var string
     */
    protected $pathPrefix = self::PATH_PREFIX;

    /**
     * @param int|string|ScopeInterface|null $website
     * @return bool
     */
    public function isGiftWrapEnabled($website = null): bool
    {
        return $this->isSetFlag(
            self::GIFTS . self::GIFT_WRAP,
            $website,
            ScopeInterface::SCOPE_WEBSITES
        );
    }

    /**
     * @param int|string|ScopeInterface|null $website
     * @return float
     */
    public function getGiftWrapFee($website = null): float
    {
        return (float)$this->getValue(
            self::GIFTS . self::GIFT_WRAP_FEE,
            $website,
            ScopeInterface::SCOPE_WEBSITES
        );
    }

    /**
     * @return bool
     */
    public function isGiftWrapModuleEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::GIFT_WRAP_PREFIX .
            self::GENERAL_BLOCK .
            self::GIFT_WRAP_MODULE,
            ScopeInterface::SCOPE_STORES
        );
    }
}
