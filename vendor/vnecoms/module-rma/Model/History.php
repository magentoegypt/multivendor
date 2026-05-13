<?php

namespace Vnecoms\RMA\Model;

use Magento\Framework\Model\AbstractModel;

class History extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\RMA\Model\ResourceModel\History');
    }
}
