<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package GeoIP Data for Magento 2 (System)
 */

namespace Amasty\Geoip\Model\Config\BackendModel;

use Magento\Framework\App\Config\Value;
use Magento\Framework\Exception\LocalizedException;

class IpValidation extends Value
{
    /**
     * @throws LocalizedException
     */
    public function beforeSave(): self
    {
        if (empty($this->getValue())) {
            throw new LocalizedException(__('Invalid Ip Address.'));
        }
        $ipIsValid = (bool)filter_var($this->getValue(), FILTER_VALIDATE_IP);
        if (!$ipIsValid) {
            throw new LocalizedException(__('Invalid Ip Address.'));
        }
        return parent::beforeSave();
    }
}
