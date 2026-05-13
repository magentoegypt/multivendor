<?php

namespace Vnecoms\Quotation\Model\Item;
use Vnecoms\Quotation\Model\Item;

class TotalCalculator
{
    public function __construct()
    {
    }

    /**
     * Processing calculation of row price for address item
     *
     * @param Item $item
     * @param int $finalPrice
     * @param int $originalPrice
     * @return $this
     */
    protected function _calculateRowTotal($item, $finalPrice, $originalPrice)
    {
        if (!$originalPrice) {
            $originalPrice = $finalPrice;
        }
        $item->setPrice($finalPrice)->setBasePrice($originalPrice);
        $item->calcRowTotal();
        return $this;
    }
}