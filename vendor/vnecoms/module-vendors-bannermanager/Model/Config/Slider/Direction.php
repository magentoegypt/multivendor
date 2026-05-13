<?php
/**
 * Created by PhpStorm.
 * User: mrtuvn
 * Date: 24/03/2017
 * Time: 11:35
 */

namespace Vnecoms\BannerManager\Model\Config\Slider;


use Magento\Framework\Option\ArrayInterface;

class Direction implements ArrayInterface
{
    /**
     * @var array
     */
    protected $options;

    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = [
                [
                    'value' => 'vertical',
                    'label' => 'Vertical'
                ],
                [
                    'value' => 'horizontal',
                    'label' => 'Horizontal'
                ]

            ];
        }

        return $this->options;
    }
}