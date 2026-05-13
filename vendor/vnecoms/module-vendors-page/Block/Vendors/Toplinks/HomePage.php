<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Vnecoms\VendorsPage\Block\Vendors\Toplinks;

/**
 * Vendor Notifications block
 */
class HomePage extends \Vnecoms\Vendors\Block\Vendors\AbstractBlock
{
    /**
     * @var \Vnecoms\VendorsPage\Helper\Data
     */
    protected $_pageHelper;
    
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $_vendorSession;
    
    /**
     * 
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Vnecoms\Vendors\Model\UrlInterface $url
     * @param \Vnecoms\VendorsPage\Helper\Data $pageHelper
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Vnecoms\Vendors\Model\UrlInterface $url,
        \Vnecoms\VendorsPage\Helper\Data $pageHelper,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        array $data = [])
    {
        $this->_vendorSession = $vendorSession;
        $this->_pageHelper = $pageHelper;
        parent::__construct($context, $url, $data);
    }
    
    /**
     * Get Vendor object
     *
     * @return \Vnecoms\Vendors\Model\Vendor
     */
    public function getVendor(){
        return $this->_vendorSession->getVendor();
    }
    
    /**
     * Get Homepage URL
     * 
     * @return string
     */
    public function getHomePageUrl(){
        return $this->_pageHelper->getUrl($this->getVendor(),'');
    }
    
    /**
     * Hide the button if the customer account is not approved.
     * 
     * @see \Magento\Framework\View\Element\Template::_toHtml()
     */
    protected function _toHtml(){
        if($this->getVendor()->getStatus() != \Vnecoms\Vendors\Model\Vendor::STATUS_APPROVED) return '';
        return parent::_toHtml();
    }
}
