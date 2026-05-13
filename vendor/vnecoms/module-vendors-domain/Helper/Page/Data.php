<?php
namespace Vnecoms\VendorsDomain\Helper\Page;

use Magento\Framework\App\ObjectManager;

class Data extends \Vnecoms\VendorsPage\Helper\Data
{
    public function getUrl($vendor, $urlKey='',$param =  array()){
        $om = ObjectManager::getInstance();
        $registry = $om->get('Magento\Framework\Registry');
        if($registry->registry('vnecoms_is_vendor_domain')){
			if(!$urlKey) return $this->_urlBuilder->getUrl($urlKey,$param);
			
            $domainHelper = $om->get('Vnecoms\VendorsDomain\Helper\Data');
            $baseUrlKey = $domainHelper->getUrlKey();
            return $baseUrlKey?
                $this->_urlBuilder->getUrl($baseUrlKey.'/'.$urlKey,$param):
                $this->_urlBuilder->getUrl($urlKey,$param);
        }
        
        return parent::getUrl($vendor, $urlKey, $param);
    }
}

