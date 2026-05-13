<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCoupon\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Data extends AbstractHelper
{
    const XML_PATH_COUPON_LENGTH = 'vendors/vendorscoupon/coupons_length';
    const XML_PATH_COUPON_FORMAT = 'vendors/vendorscoupon/coupons_format';
    const XML_PATH_COUPON_PREFIX = 'vendors/vendorscoupon/coupons_prefix';
    const XML_PATH_COUPON_SUFFIX = 'vendors/vendorscoupon/coupons_suffix';
    const XML_PATH_COUPON_DASH  = 'vendors/vendorscoupon/coupons_dash';
    const XML_PATH_SHOW_DETAIL  = 'vendors/vendorscoupon/show_discount_detail';
    const XML_PATH_SHOW_STORE_NAME  = 'vendors/vendorscoupon/show_vendor_info';

    const XML_PATH_VENDOR_COUPON  = 'vendors_coupon/manage_coupon';

    /**
     * @var array
     */
    protected $_couponParameters;

    /**
     * @var array
     */
    protected $_actions;


    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;

    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $vendorFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Vnecoms\Vendors\Helper\Data $vendorHelper
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param array $couponParameters
     * @param array $actions
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        array $couponParameters,
        array $actions
    ) {
        $this->_couponParameters    = $couponParameters;
        $this->_vendorHelper        = $vendorHelper;
        $this->vendorFactory        = $vendorFactory;
        $this->_actions = $actions;
        parent::__construct($context);
    }

    /**
     * Get coupon length
     *
     * @return int
     */
    public function getCouponLength(){
        return $this->scopeConfig->getValue(self::XML_PATH_COUPON_LENGTH);
    }

    /**
     * Get coupon format
     *
     * @return string
     */
    public function getCouponFormat(){
        return $this->scopeConfig->getValue(self::XML_PATH_COUPON_FORMAT);
    }

    /**
     * Get coupon Prefix
     *
     * @return string
     */
    public function getCouponPrefix(){
        return $this->scopeConfig->getValue(self::XML_PATH_COUPON_PREFIX);
    }

    /**
     * Get coupon Suffix
     *
     * @return string
     */
    public function getCouponSuffix(){
        return $this->scopeConfig->getValue(self::XML_PATH_COUPON_SUFFIX);
    }

    /**
     * Get coupon Dash
     *
     * @return string
     */
    public function getCouponDash(){
        return $this->scopeConfig->getValue(self::XML_PATH_COUPON_DASH);
    }

    /**
     * Get Coupon's alphabet as array of chars
     *
     * @param string $format
     * @return array|bool
     */
    public function getCharset()
    {
        return str_split($this->_couponParameters['charset'][$this->getCouponFormat()]);
    }

    /**
     * Get Coupon's actions
     *
     * @param string $format
     * @return array|bool
     */
    public function getDiscountActions()
    {
        return $this->_actions;
    }

    /**
     * Retrieve Separator
     *
     * @return string
     */
    public function getCodeSeparator()
    {
        return $this->_couponParameters['separator'];
    }

    /**
     * Can show discount detail
     *
     * @return boolean
     */
    public function canShowDiscountDetail()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SHOW_DETAIL);
    }

    /**
     * Can show store name
     *
     * @return boolean
     */
    public function canShowStoreName()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_SHOW_STORE_NAME);
    }

    /**
     * Get vendor store name
     *
     * @param int $vendorId
     */
    public function getVendorStoreName($vendorId){
        $storeName = $this->_vendorHelper->getVendorStoreName($vendorId);
        if(!$storeName){
            $vendor = $this->vendorFactory->create()->load($vendorId);
            $storeName = $vendor->getVendorId();
        }

        return $storeName;
    }
}
