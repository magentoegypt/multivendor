<?php

namespace Vnecoms\VendorsRMA\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Escalate extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('ves_rma_request_escalate', 'escalate_id');
    }
}