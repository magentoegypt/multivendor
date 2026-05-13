<?php

namespace Vnecoms\VendorsDomain\Model\ResourceModel;

class Domain extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ves_vendor_domain', 'domain_id');
    }
    
    /**
     * Remove domain by vendor id
     * 
     * @param int $vendorId
     * @return \Vnecoms\VendorsDomain\Model\ResourceModel\Domain
     */
    public function removeDomainByVendorId($vendorId)
    {
        $table = $this->getMainTable();
        $connection = $this->getConnection();
        $connection->delete($table, 'vendor_id = '.$vendorId);
        return $this;
    }
}
