<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Style Switcher for Magento 2 (System)
 */

namespace Amasty\CheckoutStyleSwitcher\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class BillingAddressDisplayOptions implements OptionSourceInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'label' => __('Payment Method'),
                'value' => 0
            ],
            [
                'label' => __('Payment Page'),
                'value' => 1
            ],
            [
                'label' => __('Below Shipping Address'),
                'value' => 2
            ]
        ];
    }
}
