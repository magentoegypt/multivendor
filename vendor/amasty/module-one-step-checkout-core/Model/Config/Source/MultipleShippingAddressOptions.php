<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class MultipleShippingAddressOptions implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('Grid'),
                'value' => 0
            ],
            [
                'label' => __('Dropdown Menu'),
                'value' => 1
            ]
        ];
    }
}
