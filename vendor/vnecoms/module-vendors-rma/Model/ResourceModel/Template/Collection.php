<?php

namespace Vnecoms\VendorsRMA\Model\ResourceModel\Template;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\VendorsRMA\Model\Request\Escalate\Template',
            'Vnecoms\VendorsRMA\Model\ResourceModel\Template'
        );
    }
}