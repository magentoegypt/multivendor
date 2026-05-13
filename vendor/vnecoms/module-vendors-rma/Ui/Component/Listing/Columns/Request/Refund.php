<?php

namespace Vnecoms\VendorsRMA\Ui\Component\Listing\Columns\Request;
use Magento\Framework\Data\OptionSourceInterface;

class Refund implements OptionSourceInterface
{

    public function toOptionArray()
    {
        return [
            ["label"=>__("Full Amount"),"value"=>"full_amount"],
            ["label"=>__("Custom Amount"),"value"=>"custom_amount"]
        ];
    }

    public function getOptionArrayGrid()
    {
        return [
            "full_amount" => __("Full Amount"),
            "custom_amount" => __("Custom Amount")
        ];
    }
}


