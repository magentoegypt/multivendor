<?php
namespace Vnecoms\SmsBulkSms\Model\Config\Source;


class MessageType implements \Magento\Framework\Option\ArrayInterface
{
    const TYPE_8BIT     = '8bit';
    const TYPE_UNICODE  = 'unicode';
    /**
     * Generate list of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [
            [
                'value' => self::TYPE_8BIT,
                'label' => __("8 Bit"),
            ],
            [
            'value' => self::TYPE_UNICODE,
            'label' => __("Unicode"),
            ],
        ];
        return $options;
    }
}
