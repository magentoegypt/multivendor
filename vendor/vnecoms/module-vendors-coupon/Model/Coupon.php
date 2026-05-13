<?php
namespace Vnecoms\VendorsCoupon\Model;

class Coupon extends \Magento\Framework\Model\AbstractModel
{    
    const ENTITY = 'coupon';
    
    /**
     * Model event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'vendor_coupon';
    
    /**
     * Name of the event object
     *
     * @var string
     */
    protected $_eventObject = 'vendor_coupon';
    
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Vnecoms\VendorsCoupon\Model\ResourceModel\Coupon');
    }

}
