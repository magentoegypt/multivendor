<?php

namespace Vnecoms\RMA\Model\ResourceModel\Reason\Store;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\RMA\Model\Request\Reason\Store',
            'Vnecoms\RMA\Model\ResourceModel\Reason\Store'
        );
    }
}
