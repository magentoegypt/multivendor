<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsRMA\Block\Adminhtml\Request\Create;

/**
 * Sales Order Email items default renderer
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class DefaultItems extends \Vnecoms\RMA\Block\Adminhtml\Request\Create\DefaultItems
{
    /**
     * get Price for each item refund
     * @param $item
     * @return float
     */
    public function getPriceItemRefund($item){
        $rowTotal = $item->getRowTotal();
        $price = $rowTotal / $item->getQtyOrdered();
        return round($price,2);
    }
}
