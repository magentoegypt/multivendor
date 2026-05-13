<?php

namespace Vnecoms\RMA\Model\Request\Reason;

use Magento\Framework\Model\AbstractModel;

class Store extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\RMA\Model\ResourceModel\Reason\Store');
    }
}
