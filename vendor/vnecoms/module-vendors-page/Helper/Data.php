<?php
/**
 * Copyright Â© 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPage\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Data extends AbstractHelper
{
    const XML_PATH_URL_KEY = 'vendors/vendorspage/url_key';
    const XML_PROFILE_BLOCK_POSITION = 'vendors/vendorspage/profile_position';
    const XML_NUM_OF_SHOWING_PRODUCT = 'vendors/vendorspage/number_of_products';
    const XML_SHOW_SELLER_DESCRIPTION = 'vendors/vendorspage/show_about';
    const XML_SHOW_SELLER_SHIPPING_POLICY = 'vendors/vendorspage/show_shipping';
    const XML_SHOW_SELLER_REFUND_POLICY = 'vendors/vendorspage/show_refund';

    /**
     * @var \Vnecoms\VendorsConfig\Helper\Data
     */
    protected $_configHelper;
    
    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;
    
    /**
     * @var \Vnecoms\Vendors\Model\VendorFactory
     */
    protected $_vendorFactory;
    
    /**
     * Constructor
     *
     * @param Context $context
     * @param \Magento\Framework\UrlInterface $urlBuilder
     * @param \Vnecoms\Vendors\Model\VendorFactory $vendorFactory
     * @param \Vnecoms\VendorsConfig\Helper\Data $configHelper
     */
    public function __construct(
        Context $context,
        \Vnecoms\Vendors\Model\VendorFactory $vendorFactory,
        \Vnecoms\VendorsConfig\Helper\Data $configHelper
    ) {
        
        $this->_configHelper = $configHelper;
        $this->_urlBuilder = $context->getUrlBuilder();
        $this->_vendorFactory = $vendorFactory;

        parent::__construct($context);
    }
    
    /**
     * Get URL Key
     *
     * @return string
     */
    public function getUrlKey()
    {
        return $this->scopeConfig->getValue(self::XML_PATH_URL_KEY);
    }
    
    /**
     * Get Vendor Url
     * @param string|VES_Vendors_Model_Vendor $vendor
     * @param string $urlKey
     * @parem array $param
     *
     * @return string
     */
    public function getUrl($vendor, $urlKey = '', $param = [])
    {
        $vendorId = $vendor;
        if ($vendor instanceof \Vnecoms\Vendors\Model\Vendor) {
            $vendorId = $vendor->getVendorId();
        } elseif (is_numeric($vendor)) {
            $vendorObj = $this->_vendorFactory->create()->load($vendor);
            $vendorId = $vendorObj->getVendorId();
        }
//         $baseUrlKey = $this->getUrlKey();
//         $tmpUrl = $this->_urlBuilder->getUrl($urlKey,$param);
    
//         if($baseUrlKey){
//             return str_replace(
//                 $this->_urlBuilder->getUrl('',array('_nosid'=>1)),
//                 $this->_urlBuilder->getUrl($baseUrlKey.'/'.$vendorId,array('_nosid'=>1)),
//                 $tmpUrl
//             );
//         }
    
//         return str_replace(
//             $this->_urlBuilder->getUrl('',array('_nosid'=>1)),
//             $this->_urlBuilder->getUrl($vendorId,array('_nosid'=>1)),
//             $tmpUrl
//         );
        
        $baseUrlKey = $this->getUrlKey();
        $tmpUrl = $this->_urlBuilder->getUrl($vendorId.'/'.$urlKey, $param);
        
        if ($baseUrlKey) {
            return str_replace(
                $this->_urlBuilder->getUrl('', ['_nosid'=>1]),
                $this->_urlBuilder->getUrl($baseUrlKey, ['_nosid'=>1]),
                $tmpUrl
            );
        }
        
        return $tmpUrl;
    }
    
    /**
     * Profile block póition
     *
     * @return string
     */
    public function getProfileBlockPosition()
    {
        return $this->scopeConfig->getValue(self::XML_PROFILE_BLOCK_POSITION);
    }
    
    /**
     * Get number of showing products on homepage.
     *
     * @return string
     */
    public function getNumOfShowingProductOnHomePage()
    {
        return $this->scopeConfig->getValue(self::XML_NUM_OF_SHOWING_PRODUCT);
    }
    
    /**
     * Can show seller's description on homepage.
     *
     * @return boolean
     */
    public function canShowSellerDescription()
    {
        return $this->scopeConfig->getValue(self::XML_SHOW_SELLER_DESCRIPTION);
    }
    
    /**
     * Can show seller's shipping policy on homepage.
     *
     * @return boolean
     */
    public function canShowSellerShippingPolicy()
    {
        return $this->scopeConfig->getValue(self::XML_SHOW_SELLER_SHIPPING_POLICY);
    }
    
    /**
     * Can show seller's refund policy on homepage.
     *
     * @return boolean
     */
    public function canShowSellerRefundPolicy()
    {
        return $this->scopeConfig->getValue(self::XML_SHOW_SELLER_REFUND_POLICY);
    }
    
    /**
     * Get vendor banner
     *
     * @param int $vendorId
     */
    public function getVendorBanner($vendorId)
    {
        return $this->_configHelper->getVendorConfig('page/general/banner', $vendorId);
    }
    
    /**
     * Get vendor description
     *
     * @param int $vendorId
     */
    public function getVendorDescription($vendorId)
    {
        return $this->_configHelper->getVendorConfig('page/general/description', $vendorId);
    }
    
    /**
     * Get vendor shipping policy
     *
     * @param int $vendorId
     */
    public function getVendorShipping($vendorId)
    {
        return $this->_configHelper->getVendorConfig('page/general/shipping_policy', $vendorId);
    }
    
    /**
     * Get vendor refund policy
     *
     * @param int $vendorId
     */
    public function getVendorRefund($vendorId)
    {
        return $this->_configHelper->getVendorConfig('page/general/refund_policy', $vendorId);
    }
    
    /**
     * Get Meta Title
     *
     * @param int $vendorId
     */
    public function getMetaTitle($vendorId)
    {
        return $this->_configHelper->getVendorConfig('page/meta/title', $vendorId);
    }
    
    /**
     * Get Meta Description
     *
     * @param int $vendorId
     */
    public function getMetaDescription($vendorId)
    {
        return $this->_configHelper->getVendorConfig('page/meta/description', $vendorId);
    }
    
    /**
     * Get Meta keywords
     *
     * @param int $vendorId
     */
    public function getMetaKeywords($vendorId)
    {
        return $this->_configHelper->getVendorConfig('page/meta/keywords', $vendorId);
    }
    
    /**
     * Get head html
     * 
     * @param int $vendorId
     */
    public function getVendorHeadHtml($vendorId){
        return $this->_configHelper->getVendorConfig('page/html_head/head_includes', $vendorId);
    }
    
    /**
     * Get footer html
     *
     * @param int $vendorId
     */
    public function getVendorFooterHtml($vendorId){
        return $this->_configHelper->getVendorConfig('page/footer/footer_absolute_footer', $vendorId);
    }
}
