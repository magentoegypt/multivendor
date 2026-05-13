<?php

namespace Vnecoms\VendorsRMA\Block\Vendor\Request\Edit;

class Amount extends \Vnecoms\VendorsRMA\Block\Vendor\Request\Edit\TabsLeft
{
    /**
     * get all refund amount history
     * @return mixed
     */
    public function getHistoryAmountRefundObject(){
        $amounts = $this->getRma()->getRefundAmountObject("all");
        return $amounts;
    }


    /**
     * format price refund by order object
     * @param $amount
     * @return mixed
     */
    public function formatPrice($amount){
        return $this->getRma()->getOrderObject()->formatPrice($amount);
    }

    /**
     * @return float|int
     */
    public function getMaxAmountRefund() {
        $amount = 0 ;
        foreach($this->getRma()->getAllItemFromRequest() as $item) {
            $orderItem = \Magento\Framework\App\ObjectManager::getInstance()->get(
                'Magento\Sales\Model\Order\Item')->load($item->getOrderItemId());
            $amount += (($orderItem->getRowTotalInclTax() - $orderItem->getDiscountAmount())
                    /$orderItem->getQtyOrdered())*$item->getQty();
        }
        return $amount;
    }
}