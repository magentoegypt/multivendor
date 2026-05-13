<?php
/**
 * Copyright (c) 2017 Vnecoms Co ltd. All rights reserved.
 */

namespace Vnecoms\Quotation\Model\ResourceModel\Quote;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Do not remove this line below
     *
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Vnecoms\Quotation\Model\Quote',
            'Vnecoms\Quotation\Model\ResourceModel\Quote'
        );
    }

    /**
     * @param $customerId
     * @return $this
     */
    public function setCustomerFilter($customerId)
    {
        $this->addFieldToFilter('customer_id', $customerId);
        return $this;
    }

    /**
     * @return $this
     */
    public function setStartingFilter()
    {
        $this->addFieldToFilter('status', array('gteq' => \Vnecoms\Quotation\Model\Quote::STATUS_PROCESSING));
        return $this;
    }

    public function setActiveFilter($isActive = true)
    {
        ($isActive == true) ? $this->addFieldToFilter('is_active', array('eq' => 1))
        : $this->addFieldToFilter('is_active', array('eq' => 0));

        return $this;
    }

    protected function _initSelect()
    {
        parent::_initSelect();
    }
}
