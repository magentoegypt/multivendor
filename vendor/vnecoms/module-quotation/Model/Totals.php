<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\Quotation\Model;

class Totals extends \Magento\Framework\DataObject
{
    protected $_quote;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    public function setQuote(Quote $quote)
    {
        $this->_quote = $quote;
    }

    public function getQuote()
    {
        return $this->_quote;
    }

    public function collect(Quote $quote)
    {
        $this->_collectItemsQtys($quote);

        $quote->setSubtotal(0);
        $total->setBaseSubtotal(0);

    }

    protected function _collectItemsQtys(Quote $quote)
    {
        $quote->setItemsCount(0);
        $quote->setItemsQty(0);

        foreach ($quote->getAllVisibleItems() as $item) {
            if ($item->getParentItem()) {
                continue;
            }

            $quote->setItemsCount($quote->getItemsCount() + 1);
            $quote->setItemsQty((float)$quote->getItemsQty() + $item->getQty());
        }
        return $this;
    }
}
