<?php

namespace Vnecoms\VendorsDomain\Plugin;

use Magento\Framework\UrlInterface;
use Vnecoms\VendorsDomain\Model\Config\Source\DomainServerParams;
use Vnecoms\VendorsDomain\Helper\Data as VendorsDomainHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class StoreBaseUrl
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
     * Store Config
     *
     * @var \Magento\Framework\App\Config\ReinitableConfigInterface
     */
    protected $config;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Vnecoms\VendorsDomain\Helper\Data $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Vnecoms\VendorsDomain\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->coreRegistry     = $coreRegistry;
        $this->helper           = $helper;
        $this->config           = $config;
        $this->request          = $request;
    }

    /**
     * (non-PHPdoc)
     * @see \Magento\Framework\Url::getBaseUrl()
     */
    public function aroundGetBaseUrl(
        \Magento\Store\Model\Store $subject,
        \Closure $proceed,
        $type = UrlInterface::URL_TYPE_LINK,
        $secure = null
    ){
        $forceUseVendorDomain = $this->coreRegistry->registry(\Vnecoms\VendorsDomain\Model\Url::USE_MARKETPLACE_DOMAIN);
        if($forceUseVendorDomain === true){
            return $proceed($type, $secure);
        }

        $domainParam = $this->getConfig(VendorsDomainHelper::XML_PATH_CURRENT_DOMAIN_PARAM, $subject->getCode());
        if($domainParam == DomainServerParams::CUSTOM_PARAM){
            $domainParam = $this->getConfig(VendorsDomainHelper::XML_PATH_CUSTOM_DOMAIN_PARAM, $subject->getCode());
        }
        $currentDomain = $this->request->getServer($domainParam);

        $oldBaseUrl = $proceed($type, $secure);

        $checkMediaUrl = false;
        if ($type == UrlInterface::URL_TYPE_MEDIA) {
            $linkTypeWeb = $proceed(UrlInterface::URL_TYPE_WEB, $secure);
            if (!preg_match("/".addcslashes($linkTypeWeb, ":/")."/is", $oldBaseUrl)) {
                $checkMediaUrl = true;
            }
        }

        $baseUrlInfo = parse_url($oldBaseUrl);
        $oldDomain = $baseUrlInfo['host'];

        if(($oldDomain == $currentDomain) || $checkMediaUrl) return $proceed($type, $secure);

        /*If current domain is not a vendor domain, return parent base url*/
        $domainObj = $this->helper->getDomainObject($currentDomain);
        if(!$domainObj) {
            return $proceed($type, $secure);
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

    public function getConfig($path, $storeCode)
    {
        $data = $this->config->getValue($path, ScopeInterface::SCOPE_STORE, $storeCode);
        if (!$data) {
            $data = $this->config->getValue($path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
        }
        return $data === false ? null : $data;
    }
}
