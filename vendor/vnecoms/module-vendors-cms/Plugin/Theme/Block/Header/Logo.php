<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCms\Plugin\Theme\Block\Header;

use Magento\Theme\Block\Html\Header\Logo as ThemeLogo;

class Logo
{
    /**
     * @var \Vnecoms\Vendors\Model\Session
     */
    protected $_vendorSession;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array
     */
    protected $_data = [];

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Vnecoms\Vendors\Helper\Data
     */
    protected $_vendorHelper;

    /** @var \Vnecoms\VendorsPage\Helper\Data  */
    protected $_pageHelper;

    /** @var \Vnecoms\VendorsConfig\Helper\Data  */
    protected $_configHelper;

    /**
     * Logo constructor.
     *
     * @param \Magento\Framework\Registry                $coreRegistry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Vnecoms\Vendors\Model\Session             $vendorSession
     * @param \Vnecoms\Vendors\Helper\Data               $vendorHelper
     * @param \Vnecoms\VendorsPage\Helper\Data           $pageHelper
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Vnecoms\Vendors\Model\Session $vendorSession,
        \Vnecoms\Vendors\Helper\Data $vendorHelper,
        \Vnecoms\VendorsPage\Helper\Data $pageHelper,
        \Vnecoms\VendorsConfig\Helper\Data $configHelper
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->storeManager = $storeManager;
        $this->_vendorSession = $vendorSession;
        $this->_vendorHelper = $vendorHelper;
        $this->_pageHelper = $pageHelper;
        $this->_configHelper = $configHelper;
    }

    public function afterIsHomePage(ThemeLogo $subject, $result)
    {
        $vendor = $this->getVendor();

        if (!$this->getVendor()) {
            $subject->getLogoSrc();
            $subject->getLogoSrc();
        }

        if ($vendor->getId()) {
            $result['logo_src'] = $this->getVendorLogoSrc();
            $result['logo_url'] = $this->getVendorLogoUrl();
        }

        //var_dump($result);exit;
        return $result;
    }

    /**
     * @param \Vnecoms\Vendors\Model\Vendor $vendor
     *
     * @return string
     */
    public function getVendorLogoSrc()
    {
        $scopeConfig = $this->_configHelper->getVendorConfig(
            'general/store_information/logo',
            $this->getVendor()->getId()
        );
        $basePath = 'ves_vendors/logo/';
        $logoPath = $basePath.$scopeConfig;

        return  $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).$logoPath;
    }

    /**
     * @return string
     */
    public function getVendorLogoUrl()
    {
        return $this->_pageHelper->getUrl($this->getVendor());
    }

    /**
     * @return mixed
     */
    protected function getVendor()
    {
        return $this->_coreRegistry->registry('vendor');
    }
}
