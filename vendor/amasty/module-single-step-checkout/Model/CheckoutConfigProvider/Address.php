<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Model\CheckoutConfigProvider;

use Amasty\Checkout\Model\Config;
use Amasty\Checkout\Model\Config\Source\Address as SourceAddress;
use Magento\Checkout\Model\ConfigProviderInterface;

class Address implements ConfigProviderInterface
{
    public const IS_BILLING_SAME_AS_SHIPPING = 'isBillingSameAsShipping';
    public const DISPLAY_BILLING_SAME_AS_SHIPPING = 'displayBillingSameAsShipping';

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }
    
    /**
     * @return array
     */
    public function getConfig(): array
    {
        $addressCheckboxState = $this->config->getAddressCheckboxState();
        return [
            static::IS_BILLING_SAME_AS_SHIPPING => $addressCheckboxState === SourceAddress::CHECKED,
            static::DISPLAY_BILLING_SAME_AS_SHIPPING => $addressCheckboxState !== SourceAddress::HIDDEN
        ];
    }
}
