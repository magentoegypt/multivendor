<?php

namespace Vnecoms\VendorsDomain\Plugin;

use Vnecoms\VendorsDomain\Model\Config\Source\DomainServerParams;
use Vnecoms\VendorsDomain\Helper\Data as VendorsDomainHelper;

class PathConfig
{
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

    public function aroundShouldBeSecure(
        \Magento\Store\Model\PathConfig $pathConfig,
        \Closure $proceed,
        $path
    ) {
        $domainParam = $this->scopeConfig->getValue(VendorsDomainHelper::XML_PATH_CURRENT_DOMAIN_PARAM);
        if($domainParam == DomainServerParams::CUSTOM_PARAM){
            $domainParam = $this->scopeConfig->getValue(VendorsDomainHelper::XML_PATH_CUSTOM_DOMAIN_PARAM);
        }
        $currentDomain = $this->request->getServer($domainParam);
        /*If current domain is not a vendor domain, return parent base url*/
        $domainObj = $this->helper->getDomainObject($currentDomain);
        if($domainObj) {
            return $domainObj->getIsSecure();
        }
        return $proceed($path);
    }
}
