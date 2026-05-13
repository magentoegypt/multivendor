<?php

namespace Vnecoms\RMA\Model\ResourceModel\Reponse;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'reponse_id';
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\RMA\Model\Reponse',
            'Vnecoms\RMA\Model\ResourceModel\Reponse'
        );
    }
}
