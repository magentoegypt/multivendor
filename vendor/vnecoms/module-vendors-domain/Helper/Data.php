<?php
namespace Vnecoms\VendorsDomain\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\ObjectManager;

/**
 * @SuppressWarnings(PHPMD.LongVariable)
 */
class Data extends AbstractHelper
{
    const USE_MARKETPLACE_DOMAIN = 'foce_use_marketplace_domain';

    const XML_PATH_CURRENT_DOMAIN_PARAM = 'vendors/vendorsdomain/current_domain_param';
    const XML_PATH_CUSTOM_DOMAIN_PARAM  = 'vendors/vendorsdomain/custom_domain_param';
    const XML_PATH_MAIN_DOMAIN          = 'vendors/vendorsdomain/main_domain';
    const XML_PATH_URL_KEY              = 'vendors/vendorsdomain/url_key';
    const XML_PATH_DNS_INSTRUCTION      = 'vendors/vendorsdomain/dns_instruction';
    const XML_PATH_REDIRECT_CHECKOUT    = 'vendors/vendorsdomain/redirect_checkout';

    /**
     * Get current domain param
     *
     * @return string
     */
    public function getCurrentDomainParam(){
        return $this->scopeConfig->getValue(self::XML_PATH_CURRENT_DOMAIN_PARAM);
    }

    /**
     * Get custom current domain param
     *
     * @return string
     */
    public function getCustomDomainParam(){
        return $this->scopeConfig->getValue(self::XML_PATH_CUSTOM_DOMAIN_PARAM);
    }

    /**
     * Get main domain
     *
     * @return string
     */
    public function getMainDomain(){
        return $this->scopeConfig->getValue(self::XML_PATH_MAIN_DOMAIN);
    }

    /**
     * Get url key
     *
     * @return string
     */
    public function getUrlKey(){
        return $this->scopeConfig->getValue(self::XML_PATH_URL_KEY);
    }

    /**
     * Get DNS Instruction
     *
     * @return string
     */
    public function getDnsInstruction(){
        return $this->scopeConfig->getValue(self::XML_PATH_DNS_INSTRUCTION);
    }

    /**
     * is redirected to main marketplace domain when checkout
     *
     * @return boolean
     */
    public function isRedirectToMainMarketplace(){
        return $this->scopeConfig->getValue(self::XML_PATH_REDIRECT_CHECKOUT);
    }

    /**
     * Get domain object by domain
     *
     * @param string $domain
     * @return \Vnecoms\VendorsDOmain\Model\Domain|boolean
     */
    public function getDomainObject($domain=''){
        $domainObj = ObjectManager::getInstance()->create('Vnecoms\VendorsDomain\Model\Domain');
        if (is_object($domain)) $domain = $domain->toString();

        if (!$domain) {
            return false;
        }

        $domainWithoutWWW = str_replace("www.", "", $domain);
        $domainWithWWW = "www.".$domainWithoutWWW;
        $domainObj->load($domainWithoutWWW, 'domain');

        if(!$domainObj->getId()){
            $domainObj->load($domainWithWWW, 'domain');
        }

        if(
            !$domainObj->getId() ||
            $domainObj->getStatus() != \Vnecoms\VendorsDomain\Model\Domain::STATUS_APPROVED
        ) return false;

        return $domainObj;
    }

    /**
     * Is currently secured request
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return boolean
     */
    public function isCurrentlySecuredRequest(\Magento\Framework\App\RequestInterface $request){
        return ($request->getServer('HTTPS') && $request->getServer('HTTPS') != 'off') ||
            $request->getServer('SERVER_PORT') == 443 ||
            $request->getServer('HTTP_X_FORWARDED_PORT') == 443;
    }
}

