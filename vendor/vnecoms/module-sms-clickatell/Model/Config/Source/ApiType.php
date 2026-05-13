<?php
namespace Vnecoms\SmsClickatell\Model\Config\Source;


class ApiType implements \Magento\Framework\Option\ArrayInterface
{
    const TYPE_HTTP = 'http';
    const TYPE_REST = 'rest';
    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => self::TYPE_HTTP,
                'label' => __("HTTP"),
            ],
            [
            'value' => self::TYPE_REST,
            'label' => __("REST"),
            ],
        ];
        return $options;
    }
}
