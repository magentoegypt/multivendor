<?php
namespace Vnecoms\VendorsDomain\Model\Vendor;

use Vnecoms\VendorsDomain\Model\Config\Source\DomainServerParams;
use Vnecoms\VendorsDomain\Helper\Data as VendorsDomainHelper;
use Magento\Framework\App\ObjectManager;

class Url extends \Vnecoms\Vendors\Model\Url
{
    const USE_MARKETPLACE_DOMAIN = 'foce_use_marketplace_domain';
    
    /**
     * @return \Magento\Framework\Registry
     */
    public function getRegistry(){
        return ObjectManager::getInstance()->get('Magento\Framework\Registry');
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
     * @see \Magento\Framework\Url::getBaseUrl()
     */
    public function getBaseUrl($params = []){
        
        if(isset($params[self::USE_MARKETPLACE_DOMAIN]) && $params[self::USE_MARKETPLACE_DOMAIN]){
            $registry = $this->getRegistry();
						if(!$registry->registry(self::USE_MARKETPLACE_DOMAIN)){
							$registry->register(self::USE_MARKETPLACE_DOMAIN, true);
						}
            $url = parent::getBaseUrl($params);
            $registry->unregister(self::USE_MARKETPLACE_DOMAIN);
            return $url;
        }
        $domainParam = $this->_scopeConfig->getValue(VendorsDomainHelper::XML_PATH_CURRENT_DOMAIN_PARAM);
        if($domainParam == DomainServerParams::CUSTOM_PARAM){
            $domainParam = $this->_scopeConfig->getValue(VendorsDomainHelper::XML_PATH_CUSTOM_DOMAIN_PARAM);
        }        
        $currentDomain = $this->_request->getServer($domainParam);
        $oldBaseUrl = parent::getBaseUrl($params);
        $baseUrlInfo = parse_url($oldBaseUrl);
        $oldDomain = $baseUrlInfo['host'];

        if($oldDomain == $currentDomain) return parent::getBaseUrl($params);
        /*If current domain is not a vendor domain, return parent base url*/
        $helper = $this->getDomainHelper();
        $domainObj = $helper->getDomainObject($currentDomain);
        if(!$domainObj) {
            return parent::getBaseUrl($params);
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
