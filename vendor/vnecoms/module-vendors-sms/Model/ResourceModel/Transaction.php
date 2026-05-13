<?php
namespace Vnecoms\VendorsSms\Model\ResourceModel;

/**
 * Sms mysql resource
 */
class Transaction extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ves_sms_credit_transaction', 'transaction_id');
    }
}
