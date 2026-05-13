<?php

namespace Vnecoms\RMA\Model\ResourceModel\Reason;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'reason_id';
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\RMA\Model\Reason',
            'Vnecoms\RMA\Model\ResourceModel\Reason'
        );
    }
}
