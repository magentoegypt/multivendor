<?php 
namespace MagentoEgypt\VendorExtend\Model;

class Quote extends \Vnecoms\Quotation\Model\Quote
{
    /**
     * Get All items of collection
     * @return array
     */
    public function getAllItems()
    {
        $items = [];
        foreach ($this->getItemsCollection() as $item) {
            if (!$item->isDeleted()) {
                if($item->getId()) $item->calcRowTotal();
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * Get array of all items what can be display directly (without parent item)
     *
     * @return \Vnecoms\Quotation\Model\Item[]
     */
    public function getAllVisibleItems()
    {
        $items = [];
        foreach ($this->getItemsCollection() as $item) {
            if (!$item->isDeleted() && !$item->getParentItemId()) {
                if($item->getId()) $item->calcRowTotal();
                $items[] = $item;
            }
        }
        return $items;
    }
}