<?php
namespace Vnecoms\VendorsSms\Model;

class Transaction extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Prefix of model events names
     * @var string
     */
    protected $_eventPrefix = 'vsms_transaction';
    
    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'transaction';
    
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsSms\Model\ResourceModel\Transaction');
    }
}
