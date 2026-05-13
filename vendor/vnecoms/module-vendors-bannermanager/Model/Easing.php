<?php

namespace Vnecoms\BannerManager\Model;

/**
 * Class Model Easing
 * @category Vnecoms
 * @package  Vnecoms_BannerManager
 * @module   BannerManager
 * @author   Vnecoms Developer Team
 */
class Easing extends \Magento\Framework\DataObject
{
    /**
     * get option of easing effects
     *
     * @return array
     */
    public function getOptionArray()
    {
        $options = [];
        $options[] = [
            [
            'label'     => __('--------- Please choose effect -------'),
            'value'     => '',
            ],
            [
                'label'     => __('Fade'),
                'value'     => 'fade',
            ],
            [
                'label'     => __('Slide'),
                'value'     => 'slide',
            ],
            [
                'label'     => __('Cube'),
                'value'     => 'cube',
            ],
            [
                'label'     => __('Coverflow'),
                'value'     => 'coverflow',
            ],
            [
                'label'     => __('Flip'),
                'value'     => 'flip',
            ],
        ];

        return $options;
    }
}
