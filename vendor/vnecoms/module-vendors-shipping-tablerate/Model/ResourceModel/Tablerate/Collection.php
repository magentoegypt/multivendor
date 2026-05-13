<?php

namespace Vnecoms\VendorsShippingTableRate\Model\ResourceModel\Tablerate;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'rate_id';
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\VendorsShippingTableRate\Model\Tablerate',
            'Vnecoms\VendorsShippingTableRate\Model\ResourceModel\Tablerate'
        );
    }
}