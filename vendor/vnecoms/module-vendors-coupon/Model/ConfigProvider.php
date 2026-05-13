<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Model;

use Magento\Checkout\Model\Cart as CustomerCart;
use Vnecoms\Vendors\Model\VendorFactory;
use Vnecoms\VendorsCoupon\Helper\Data as CouponHelper;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class ConfigProvider implements \Magento\Checkout\Model\ConfigProviderInterface
{
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    
    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $_vendorFactory;
    
    /**
     * @var \Vnecoms\VendorsCoupon\Helper\Data
     */
    protected $_couponHelper;
    
    /**
     * 
     * @param CustomerCart $cart
     * @param VendorFactory $vendorFactory
     * @param VendorHelper $vendorHelper
     */
    public function __construct(
        CustomerCart $cart,
        VendorFactory $vendorFactory,
        CouponHelper $couponHelper
    ) {
        $this->cart = $cart;
        $this->_vendorFactory = $vendorFactory;
        $this->_couponHelper = $couponHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $output = [];
        $vendors = [];
        foreach($this->cart->getQuote()->getAllItems() as $item){
            if(!($vendorId = $item->getProduct()->getVendorId())) continue;
            if(!isset($vendors['vendor_'.$vendorId])){
                $vendors['vendor_'.$vendorId] = $this->_couponHelper->getVendorStoreName($vendorId);
            }
        }
        $output['coupon_vendor_list'] = $vendors;
        $output['coupon_show_seller_store'] = boolval($this->_couponHelper->canShowStoreName());
        $output['coupon_show_detail'] = boolval($this->_couponHelper->canShowDiscountDetail());
        return $output;
    }
}
