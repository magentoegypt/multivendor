<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout for Magento 2
 */

namespace Amasty\Checkout\Model\Config\Backend;

use Magento\Framework\App\Config\Value as ConfigValue;
use Magento\Framework\Exception\LocalizedException;

class MaxPhoneLength extends ConfigValue
{
    /**
     * @return MaxPhoneLength
     * @throws LocalizedException
     */
    public function beforeSave()
    {
        if ($this->getValue() < $this->getFieldsetDataValue('phone_min_length')) {
            throw new LocalizedException(
                __('Please correct the values for minimum and maximum text length validation rules.')
            );
        }

        return parent::beforeSave();
    }
}
