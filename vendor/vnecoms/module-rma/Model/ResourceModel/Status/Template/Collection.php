<?php

namespace Vnecoms\RMA\Model\ResourceModel\Status\Template;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\RMA\Model\Request\Status\Template',
            'Vnecoms\RMA\Model\ResourceModel\Status\Template'
        );
    }
}
