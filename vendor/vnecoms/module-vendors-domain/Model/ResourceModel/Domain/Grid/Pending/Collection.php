<?php
namespace Vnecoms\VendorsDomain\Model\ResourceModel\Domain\Grid\Pending;

class Collection extends \Vnecoms\VendorsDomain\Model\ResourceModel\Domain\Grid\Collection
{

    /**
     * Init collection select
     *
     * @return $this
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->getSelect()->where('main_table.status=?', \Vnecoms\VendorsDomain\Model\Domain::STATUS_PENDING);
        return $this;
    }
}
