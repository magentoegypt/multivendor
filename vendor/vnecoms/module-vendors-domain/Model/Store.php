<?php
namespace Vnecoms\VendorsDomain\Model;

use Magento\Framework\UrlInterface;
use Vnecoms\VendorsDomain\Model\Config\Source\DomainServerParams;
use Vnecoms\VendorsDomain\Helper\Data as VendorsDomainHelper;
use Magento\Framework\App\ObjectManager;

class Store extends \Magento\Store\Model\Store
{
    /**
    * @return \Magento\Framework\Registry
    */
    public function getRegistry(){
        return ObjectManager::getInstance()->get('Magento\Framework\Registry');;
    }
    
    /**
     * Get domain helper
     * @return \Vnecoms\VendorsDomain\Helper\Data
     */
    public function getDomainHelper(){
        return ObjectManager::getInstance()->get('Vnecoms\VendorsDomain\Helper\Data');
    }
    
    /**
     * (non-PHPdoc)
     * @see \Magento\Store\Model\Store::getBaseUrl()
     */
    public function getBaseUrl($type = UrlInterface::URL_TYPE_LINK, $secure = null)
    {
        $forceUseVendorDomain = $this->getRegistry()->registry(\Vnecoms\VendorsDomain\Model\Url::USE_MARKETPLACE_DOMAIN);
        if($forceUseVendorDomain === true){
            return parent::getBaseUrl($type, $secure);
        }
        
        $domainParam = $this->getConfig(VendorsDomainHelper::XML_PATH_CURRENT_DOMAIN_PARAM);
        if($domainParam == DomainServerParams::CUSTOM_PARAM){
            $domainParam = $this->getConfig(VendorsDomainHelper::XML_PATH_CUSTOM_DOMAIN_PARAM);
        }
        $currentDomain = $this->_request->getServer($domainParam);
        
        $oldBaseUrl = parent::getBaseUrl($type, $secure);
        $baseUrlInfo = parse_url($oldBaseUrl);
        $oldDomain = $baseUrlInfo['host'];
        if($oldDomain == $currentDomain) return parent::getBaseUrl($type, $secure);
        
        /*If current domain is not a vendor domain, return parent base url*/
        $helper = $this->getDomainHelper();
        $domainObj = $helper->getDomainObject($currentDomain);
        if(!$domainObj) {
            return parent::getBaseUrl($type, $secure);
        }
        
        $newBaseUrl = str_replace($oldDomain, $domainObj->getDomain(), $oldBaseUrl);
        if($helper->isCurrentlySecuredRequest($this->_request)){
            $newBaseUrl = str_replace("http://", "https://", $newBaseUrl);
        }else{
            $newBaseUrl = str_replace("https://", "http://", $newBaseUrl);
        }
        return $newBaseUrl;
    }
}
