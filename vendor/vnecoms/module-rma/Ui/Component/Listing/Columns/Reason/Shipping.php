<?php

namespace Vnecoms\RMA\Ui\Component\Listing\Columns\Reason;

use Magento\Framework\Data\OptionSourceInterface;

class Shipping implements OptionSourceInterface
{
    const DO_NOT_SHOW = 0;
    const STORE_OWNER = 1;
    const CUSTOMER = 2;

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ["label"=>__("Do not show"), "value"=> self::DO_NOT_SHOW],
            ["label"=>__("Store Owner"), "value"=> self::STORE_OWNER],
            ["label"=>__("Customer"), "value"=> self::CUSTOMER]
        ];
    }

    /**
     * @return array
     */
    public function getOptionArrayGrid()
    {
        return [
            self::DO_NOT_SHOW => __("Do not show"),
            self::STORE_OWNER => __("Store Owner"),
            self::CUSTOMER => __("Customer")
        ];
    }
}
