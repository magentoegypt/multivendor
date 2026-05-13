<?php

namespace Vnecoms\RMA\Model;

use Magento\Framework\Model\AbstractModel;

class Address extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\RMA\Model\ResourceModel\Address');
    }
}
