<?php
/**
 * Created by PhpStorm.
 * User: mrtuvn
 * Date: 24/03/2017
 * Time: 16:31
 */

namespace Vnecoms\BannerManager\Model\Config\Source;


class BannerType implements \Magento\Framework\Option\ArrayInterface
{

    /**
     * @var array
     */
    protected $options;

    /**
     * Get possible sharing configuration options
     *
     * @return array
     */
    public function toOptionArray()
    {

        if (!$this->options) {
            $this->options = [
                [
                    'label' => __('Static Image'),
                    'value' => 0
                ],
                [
                    'label' => __('Banner Slider'),
                    'value' => 1
                ]
            ];
        }

        return $this->options;

    }


}