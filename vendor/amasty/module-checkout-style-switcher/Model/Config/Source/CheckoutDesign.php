<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Style Switcher for Magento 2 (System)
 */

namespace Amasty\CheckoutStyleSwitcher\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CheckoutDesign implements OptionSourceInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 0, 'label' => __('Classic')],
            ['value' => 1, 'label' => __('Modern')]
        ];
    }
}
