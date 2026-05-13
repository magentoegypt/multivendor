<?php
namespace Vnecoms\VendorsDomain\Model;

use Vnecoms\Vendors\Model\Vendor;
use Magento\Framework\App\ObjectManager;

class Domain extends \Magento\Framework\Model\AbstractModel
{

    const STATUS_PENDING    = 1;
    const STATUS_APPROVED   = 2;
    const STATUS_UNAPPROVED = 0;
    
    
    /**
     * Model event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'vendor_domain';
    
    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'vendor_domain';
    
    /**
     * @var \Vnecoms\Vendors\Model\Vendor
     */
    protected $vendor;
    
    /**
     * Initialize customer model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Vnecoms\VendorsDomain\Model\ResourceModel\Domain');
    }
    
    /**
     * Get Vendor Object
     * 
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor(){
        if(!$this->vendor){
            $this->vendor = ObjectManager::getInstance()->create('Vnecoms\Vendors\Model\Vendor');
            $this->vendor->load($this->getVendorId());
        }
        
        return $this->vendor;
    }
}
