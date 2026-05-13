<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Block\Frontend\Customer\NewRequest;

class DefaultItems extends \Vnecoms\RMA\Block\Frontend\Customer\NewRequest\DefaultItems
{
    /**
     * get Price for each item refund
     * @param $item
     * @return float
     */
    public function getPriceItemRefund($item){
        $rowTotal = $item->getRowTotalInclTax() - $item->getDiscountAmount();
        $price = $rowTotal / $item->getQtyOrdered();
        return round($price,2);
    }

}
