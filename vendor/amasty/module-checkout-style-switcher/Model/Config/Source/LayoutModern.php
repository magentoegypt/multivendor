<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Style Switcher for Magento 2 (System)
 */

namespace Amasty\CheckoutStyleSwitcher\Model\Config\Source;

use Amasty\CheckoutCore\Model\Config\Source\Layout;
use Magento\Framework\Data\OptionSourceInterface;

class LayoutModern implements OptionSourceInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => Layout::ONE_COLUMN, 'label' => __('1 Column')],
            ['value' => Layout::TWO_COLUMNS, 'label' => __('2 Columns (1 Column with a Fixed Order Summary Sidebar)')],
            ['value' => Layout::THREE_COLUMNS, 'label' => __('3 Columns')]
        ];
    }
}
