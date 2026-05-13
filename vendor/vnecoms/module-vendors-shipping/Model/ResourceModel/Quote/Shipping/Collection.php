<?php

namespace Vnecoms\VendorsShipping\Model\ResourceModel\Quote\Shipping;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\VendorsShipping\Model\Quote\Shipping',
            'Vnecoms\VendorsShipping\Model\ResourceModel\Quote\Shipping'
        );
    }
}
