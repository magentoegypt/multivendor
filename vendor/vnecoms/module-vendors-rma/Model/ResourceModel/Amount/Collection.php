<?php

namespace Vnecoms\VendorsRMA\Model\ResourceModel\Amount;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\VendorsRMA\Model\Request\Refund\Amount',
            'Vnecoms\VendorsRMA\Model\ResourceModel\Amount'
        );
    }
}