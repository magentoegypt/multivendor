<?php

namespace Vnecoms\VendorsShipping\Model\Quote;

use Magento\Framework\Model\AbstractModel;

class Shipping extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsShipping\Model\ResourceModel\Quote\Shipping');
    }
}
