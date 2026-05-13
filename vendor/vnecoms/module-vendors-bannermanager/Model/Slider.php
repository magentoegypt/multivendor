<?php

namespace Vnecoms\BannerManager\Model;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Model Easing
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Slider extends \Magento\Framework\DataObject implements ArrayInterface
{
    protected $options;

    const SLIDE_FLEX_TYPE = 1;
    const SLIDE_SLICK_TYPE = 2;
    const SLIDE_SWIPE_TYPE = 3;

    const SLIDE_FLEX_MODE_DEFAULT = 4;
    const SLIDE_SLICK_MODE_DEFAULT = 5;

    const SLIDE_SWIPE_MODE_DEFAULT = 6;
    const SLIDE_SWIPE_MODE_RESPONSIVE = 7;
    const SLIDE_SWIPE_MODE_PARALLAX = 8;
    const SLIDE_SWIPE_MODE_FREE = 9;
    const SLIDE_SWIPE_MODE_LAZY = 10;

    /**
     * get option of easing effects
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [
                [
                    'label'     => __('-------- Please choose slide type --------'),
                    'value'     => '',
                ],
                [
                    'value'     => static::SLIDE_FLEX_TYPE,
                    'label'     => __('FlexSlider'),
                ],
                /*[
                    'value'     => static::SLIDE_SLICK_TYPE,
                    'label'     => __('Slick Slider'),
                ],
                [
                    'value'     => static::SLIDE_SWIPE_TYPE,
                    'label'     => __('Swipe Slider'),
                ]*/

            ];
        }

        return $this->options;


    }


}
