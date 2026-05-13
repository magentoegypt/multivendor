<?php

namespace Vnecoms\RMA\Model\ResourceModel\Queue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'queue_id';
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\RMA\Model\Request\Queue',
            'Vnecoms\RMA\Model\ResourceModel\Queue'
        );
    }
}
