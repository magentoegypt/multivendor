<?php

namespace Vnecoms\VendorsCategory\Model\ResourceModel\Layer\Filter;

class Price extends \Vnecoms\VendorsLayerNavigation\Model\ResourceModel\Layer\Filter\Price
{
    /**
     * Retrieve clean select with joined price index table
     *
     * @return \Magento\Framework\DB\Select
     */
    protected function _getSelect()
    {
        return parent::_getSelect();
    }
}
