<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Style Switcher for Magento 2 (System)
 */

namespace Amasty\CheckoutStyleSwitcher\Plugin\Checkout\Model\Admin\BillingAddressDisplayOptions;

use Magento\Checkout\Model\Adminhtml\BillingAddressDisplayOptions;

class AddOption
{
    /**
     * @param BillingAddressDisplayOptions $subject
     * @param array $result
     * @return array
     */
    public function afterToOptionArray(BillingAddressDisplayOptions $subject, array $result): array
    {
        $result[] = [
            'label' => __('Below Shipping Address (Amasty One Step Checkout)'),
            'value' => 2
        ];

        return $result;
    }
}
