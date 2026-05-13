<?php

namespace Vnecoms\VendorsDomain\Plugin;

use Vnecoms\VendorsDomain\Model\Config\Source\DomainServerParams;

class FrontNameResolver
{
    const XML_PATH_ENABLE = 'vendors/vendorsdomain/enable_login';
    
    const XML_PATH_USE_CUSTOM_VENDOR_PATH = 'vendors/vendorsdomain/use_custom_path';
    
    const XML_PATH_CUSTOM_VENDOR_PATH = 'vendors/vendorsdomain/custom_path';
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Request instance
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    
    /**
     * @var \Vnecoms\VendorsDomain\Helper\Data
     */
    protected $helper;
    
    /**
     * @var string
     */
    protected $defaultFrontName = 'admin';
    
    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Vnecoms\VendorsDomain\Helper\Data $helper
    ) {
        $this->request      = $request;
        $this->scopeConfig  = $scopeConfig;
        $this->helper       = $helper;
    }

    /**
     * @param \Vnecoms\Vendors\App\Area\FrontNameResolver $subject
     * @param \Closure $proceed
     * @param string $checkHost
     * @return string
     */
    public function aroundGetFrontName(
        \Vnecoms\Vendors\App\Area\FrontNameResolver $subject,
        \Closure $proceed,
        $checkHost = false
    ) {

        if (
            $this->scopeConfig->getValue(self::XML_PATH_ENABLE) &&
            $checkHost &&
            !$this->isHostVendorBackend()
        ) {
            return $proceed($checkHost);
        }
        $isCustomPathUsed = (bool)(string)$this->scopeConfig->getValue(self::XML_PATH_USE_CUSTOM_VENDOR_PATH);
        if ($isCustomPathUsed) {
            return (string)$this->scopeConfig->getValue(self::XML_PATH_CUSTOM_VENDOR_PATH);
        }
        return $this->defaultFrontName;
    }
    
    /**
     * Return whether the host from request is the vendor host
     *
     * @return bool
     */
    public function isHostVendorBackend()
    {
        $domainParam = $this->helper->getCurrentDomainParam();
        if($domainParam == DomainServerParams::CUSTOM_PARAM){
            $domainParam = $this->helper->getCustomDomainParam();
        }
        $currentDomain = $this->request->getServer($domainParam);
        $domain = $this->helper->getDomainObject($currentDomain);
        if(!$domain) return false;
        return true;
    }
}
