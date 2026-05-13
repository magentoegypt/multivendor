<?php
namespace Vnecoms\VendorsDomain\Model\ResourceModel\Domain;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * App page collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'domain_id';


    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsDomain\Model\Domain', 'Vnecoms\VendorsDomain\Model\ResourceModel\Domain');
    }
}
