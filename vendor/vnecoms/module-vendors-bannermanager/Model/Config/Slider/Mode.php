<?php
/**
 * Created by PhpStorm.
 * User: mrtuvn
 * Date: 24/03/2017
 * Time: 11:35
 */

namespace Vnecoms\BannerManager\Model\Config\Slider;

use Vnecoms\BannerManager\Model\Slider;

class Mode implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * To option array.
     *
     * @return array
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [
                [
                    'label'     => __('--------- Please choose slide mode ---------'),
                    'value'     => '',
                ],
                [
                    'value' => Slider::SLIDE_FLEX_MODE_DEFAULT,
                    'label' => __('Flexslider Default')
                ],
                /*[
                    'value' => Slider::SLIDE_SLICK_MODE_DEFAULT,
                    'label' => __('Slick Default')
                ],
                [
                    'value' => Slider::SLIDE_SWIPE_MODE_DEFAULT,
                    'label' => __('Swipe Default')
                ],
                [
                    'value' => Slider::SLIDE_SWIPE_MODE_RESPONSIVE,
                    'label' => __('Swipe Responsive')
                ],
                [
                    'value' => Slider::SLIDE_SWIPE_MODE_PARALLAX,
                    'label' => __('Swipe Parallax')
                ],
                [
                    'value' => Slider::SLIDE_SWIPE_MODE_FREE,
                    'label' => __('Swipe Freemode')
                ],
                [
                    'value' => Slider::SLIDE_SWIPE_MODE_LAZY,
                    'label' => __('Swipe Lazyload ')
                ]*/

            ];
        }

        return $this->options;
    }
}