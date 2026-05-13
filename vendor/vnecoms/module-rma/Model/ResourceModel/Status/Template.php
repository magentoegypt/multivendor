<?php

namespace Vnecoms\RMA\Model\ResourceModel\Status;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Template extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('ves_rma_status_template', 'template_id');
    }
}
