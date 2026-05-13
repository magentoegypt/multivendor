<?php

namespace Vnecoms\RMA\Model\ResourceModel\History;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\RMA\Model\History',
            'Vnecoms\RMA\Model\ResourceModel\History'
        );
    }
}
