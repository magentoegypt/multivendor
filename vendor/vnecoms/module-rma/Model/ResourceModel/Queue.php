<?php

namespace Vnecoms\RMA\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Queue extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('ves_rma_request_email_queue', 'queue_id');
    }
}
