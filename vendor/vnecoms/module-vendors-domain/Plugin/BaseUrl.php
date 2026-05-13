<?php

namespace Vnecoms\VendorsDomain\Plugin;

use Vnecoms\VendorsDomain\Model\Config\Source\DomainServerParams;
use Vnecoms\VendorsDomain\Helper\Data as VendorsDomainHelper;

class BaseUrl
{    
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    
    /**
     * @var \Vnecoms\VendorsDomain\Helper\Data
     */
    protected $helper;
    
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;
    
    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Vnecoms\VendorsDomain\Helper\Data $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\VendorsDomain\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->coreRegistry     = $coreRegistry;
        $this->helper           = $helper;
        $this->scopeConfig      = $scopeConfig;
        $this->request          = $request;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Magento\Framework\Url::getBaseUrl()
     */
    public function aroundGetBaseUrl(
        \Magento\Framework\UrlInterface $subject,
        \Closure $proceed,
        $params = []
    ){
        $useMarketplaceDomainKey = \Vnecoms\VendorsDomain\Helper\Data::USE_MARKETPLACE_DOMAIN;
        
        if(isset($params[$useMarketplaceDomainKey]) && $params[$useMarketplaceDomainKey]){
						if(!$this->coreRegistry->registry($useMarketplaceDomainKey)){
							$this->coreRegistry->register($useMarketplaceDomainKey, true);
						}
            $url = $proceed($params);
            $this->coreRegistry->unregister($useMarketplaceDomainKey);
            return $url;
        }
        $domainParam = $this->scopeConfig->getValue(VendorsDomainHelper::XML_PATH_CURRENT_DOMAIN_PARAM);
        if($domainParam == DomainServerParams::CUSTOM_PARAM){
            $domainParam = $this->scopeConfig->getValue(VendorsDomainHelper::XML_PATH_CUSTOM_DOMAIN_PARAM);
        }
        $currentDomain = $this->request->getServer($domainParam);
        $oldBaseUrl = $proceed($params);
        $baseUrlInfo = parse_url($oldBaseUrl);
        $oldDomain = $baseUrlInfo['host'];
        if($oldDomain == $currentDomain) return $proceed($params);
        
        /*If current domain is not a vendor domain, return parent base url*/
        $domainObj = $this->helper->getDomainObject($currentDomain);
        if(!$domainObj) {
            return $proceed($params);
        }
        
        if(!$this->coreRegistry->registry('current_vendor_domain')){
            $this->coreRegistry->register('current_vendor_domain', $domainObj);
        }
        $newBaseUrl = str_replace($oldDomain, $domainObj->getDomain(), $oldBaseUrl);
        if($this->helper->isCurrentlySecuredRequest($this->request)){
            $newBaseUrl = str_replace("http://", "https://", $newBaseUrl);
        }else{
            $newBaseUrl = str_replace("https://", "http://", $newBaseUrl);
        }
        return $newBaseUrl;
    }
}
