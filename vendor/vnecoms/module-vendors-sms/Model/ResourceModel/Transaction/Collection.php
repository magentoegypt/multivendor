<?php
namespace Vnecoms\VendorsSms\Model\ResourceModel\Transaction;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * App page collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'transaction_id';


    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsSms\Model\Transaction', 'Vnecoms\VendorsSms\Model\ResourceModel\Transaction');
    }

}
