<?php
namespace Vnecoms\SmsThaiBulkSms\Model\Config\Source;


class MessageType implements \Magento\Framework\Option\ArrayInterface
{
    const TYPE_STANDARD     = 'standard';
    const TYPE_PREMIUM      = 'premium';
    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => self::TYPE_STANDARD,
                'label' => __("Standard"),
            ],
            [
                'value' => self::TYPE_PREMIUM,
                'label' => __("Premium"),
            ],
        ];
        return $options;
    }
}
