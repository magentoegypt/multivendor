<?php


namespace Vnecoms\RMA\Model\Source;

class Status extends \Magento\Framework\DataObject
{

    const STATUS_ENABLED    = 1;
    const STATUS_DISABLED   = 0;
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            self::STATUS_ENABLED    => __('Enabled'),
            self::STATUS_DISABLED   => __('Disabled')
        ];
    }
}
