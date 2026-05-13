<?php
/**
* Copyright 2016 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Model\Source;

/**
 * Class Status
 * @package Vnecoms\BannerManager\Model\Source
 */
class Status implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Status values
     */
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    /**
     * @return array
     */
    public function getOptionArray()
    {
        $optionArray = ['' => ' '];
        foreach ($this->toOptionArray() as $option) {
            $optionArray[$option['value']] = $option['label'];
        }
        return $optionArray;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::STATUS_ENABLED,  'label' => __('Enabled')],
            ['value' => self::STATUS_DISABLED,  'label' => __('Disabled')],
        ];
    }
}
