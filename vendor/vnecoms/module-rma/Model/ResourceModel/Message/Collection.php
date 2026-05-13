<?php

namespace Vnecoms\RMA\Model\ResourceModel\Message;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\RMA\Model\Message',
            'Vnecoms\RMA\Model\ResourceModel\Message'
        );
    }
}
