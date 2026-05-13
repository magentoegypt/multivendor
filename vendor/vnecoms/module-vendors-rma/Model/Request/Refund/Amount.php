<?php

namespace Vnecoms\VendorsRMA\Model\Request\Refund;

use Magento\Framework\Model\AbstractModel;

class Amount extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsRMA\Model\ResourceModel\Amount');
    }

}