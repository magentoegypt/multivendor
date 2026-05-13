<?php

namespace Vnecoms\RMA\Model\ResourceModel\Reason;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Store extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('ves_rma_reason_store', 'reason_store_id');
    }
}
