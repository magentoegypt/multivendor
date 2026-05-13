<?php

namespace Vnecoms\RMA\Model\ResourceModel\Status;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\RMA\Model\Status',
            'Vnecoms\RMA\Model\ResourceModel\Status'
        );
    }
    /**
     * Init select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        /*
        $this->getSelect()->joinLeft(
            array(
            'template'=>$this->getTable('ves_rma_status_template')),
            'template_status_id = status_id',
            array(
                "template_customer_notify",
                "template_admin_notify"
            )
        );
        */
        return $this;
    }
}
