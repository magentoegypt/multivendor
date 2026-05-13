<?php
namespace Vnecoms\VendorsPriceComparison\Model;

class Queue extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize customer model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Vnecoms\VendorsPriceComparison\Model\ResourceModel\Queue');
    }
}
