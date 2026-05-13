<?php
namespace Vnecoms\VendorsDomain\Block\Html\Header;

class Logo extends \Vnecoms\Vendors\Block\Profile\Logo
{
    /**
     * Is Homepage
     * 
     * @return boolean
     */
    public function isHomePage(){
        return false;
    }
    
    /**
     * (non-PHPdoc)
     * @see \Vnecoms\Vendors\Block\Profile::getNoLogoUrl()
     */
    public function getNoLogoUrl(){
        return $this->getViewFileUrl('Vnecoms_VendorsDomain::images/no-logo.jpg');
    }
    
    /**
     * Get Logo URL
     */
    public function getLogoUrl()
    {
        $scopeConfig = $this->_configHelper->getVendorConfig(
            'page/general/logo',
            $this->getVendor()->getId()
        );
        $basePath = 'ves_vendors/header_logo/';
        $path =  $basePath. $scopeConfig;
    
    
        if ($scopeConfig && $this->checkIsFile($path)) {
            $logoUrl = $this->_storeManager->getStore()
            ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . $path;
            return $logoUrl;
        }
    
        return $this->getNoLogoUrl();
    }
}
