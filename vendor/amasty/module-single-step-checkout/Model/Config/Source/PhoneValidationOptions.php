<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class PhoneValidationOptions implements OptionSourceInterface
{
    public const PHONE_VALIDATION_NONE = 0;
    public const PHONE_VALIDATION_NUMERIC = 1;
    public const PHONE_VALIDATION_CHARACTERS = 2;

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        return [
            [
                'label' => __('None'),
                'value' => self::PHONE_VALIDATION_NONE
            ],
            [
                'label' => __('Numeric Only'),
                'value' => self::PHONE_VALIDATION_NUMERIC
            ],
            [
                'label' => __('Numeric and Special Characters'),
                'value' => self::PHONE_VALIDATION_CHARACTERS
            ]
        ];
    }
}
