<?php

namespace Vnecoms\VendorsRMA\Block\Frontend\Guest\NewRequest;

class Info extends \Vnecoms\RMA\Block\Frontend\Guest\NewRequest\Info
{
    /**
     * get Refund Amount Type
     * @return array
     */
    public function getRefundAmountType(){
        return [
            "" => __("--- Select Refund Amount ---"),
            "full_amount" => __("Full Amount"),
            "custom_amount" => __("Custom Amount"),
        ];
    }

}
