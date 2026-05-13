<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ProductEditingType implements OptionSourceInterface
{
    public const TYPE_MANUALLY = 0;
    public const TYPE_AUTOMATICALLY = 1;
    
    public function toOptionArray(): array
    {
        return [
            [
                'label' => __('Manually'),
                'value' => self::TYPE_MANUALLY
            ],
            [
                'label' => __('Automatically'),
                'value' => self::TYPE_AUTOMATICALLY
            ]
        ];
    }
}
