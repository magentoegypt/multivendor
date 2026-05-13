<?php
namespace Vnecoms\VendorsProductImportExport\Model\Import;

class Data extends \Magento\Framework\Model\AbstractModel
{
    const ENTITY = 'vendor_import_queue';
    
    const STATUS_DRAFT = 0;
    const STATUS_IN_PROCESS = 1;
    const STATUS_IMPORTING = 2;
    const STATUS_ERROR = 3;
    
    const BEHAVIOR_APPEND = 'append';
    const BEHAVIOR_DELETE = 'delete';
    
    /**
     * Model event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'vendor_import_queue';
    
    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'import_data';

    /**
     * Initialize customer model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Vnecoms\VendorsProductImportExport\Model\ResourceModel\Import\Data');
    }
}
