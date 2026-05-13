<?php
/**
* Copyright 2017 Vnecoms. All rights reserved.
* See LICENSE.txt for license details.
*/

namespace Vnecoms\BannerManager\Model\Source;

/**
 * Class Position
 * @package Vnecoms\BannerManager\Model\Source
 */
class Template implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * Position values
     */
    const SWIPE_DEFAULT = 1;
    const SWIPE_RESPONSIVE = 2;
    const SWIPE_PARALLAX = 3;
    const SWIPE_RTL = 4;

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
            ['value' => self::SWIPE_DEFAULT,  'label' => __('Swipe Default')],
            ['value' => self::SWIPE_RESPONSIVE,  'label' => __('Swipe Responsive')],
            ['value' => self::SWIPE_PARALLAX,  'label' => __('Swipe Parallax')],
            ['value' => self::SWIPE_RTL,  'label' => __('Swipe RTL')]
        ];
    }
}
