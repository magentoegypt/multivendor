<?php
namespace Vnecoms\VendorsDomain\Model\Config\Backend;

use Vnecoms\VendorsDomain\Model\Config\Source\Url as SourceUrl;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Vnecoms\VendorsDomain\Model\Domain;

class Url extends \Vnecoms\VendorsConfig\Model\Config
{
    /**
     * @return $this
     */
    public function beforeSave()
    {
        $path = $this->getPath();
        $vendorId = $this->getVendorId();
        $savedValue = $this->_configHelper->getVendorConfig($path, $vendorId);
        $currentValue = $this->getValue();
        $om = ObjectManager::getInstance();
        $domainHelper = $om->get('Vnecoms\VendorsDomain\Helper\Data');
        $groupData = $this->getData('groups');
        $isSecure = isset($groupData['base_url']['fields']['url_type']['secure']) && $groupData['base_url']['fields']['url_type']['secure'];
        switch($currentValue){
            case SourceUrl::URL_SUB_FOLDER:
                /*Remove custom domain from domain table.*/
                $domain = $om->create('Vnecoms\VendorsDomain\Model\Domain')
                    ->getResource()
                    ->removeDomainByVendorId($vendorId);
                break;
            case SourceUrl::URL_SUB_DOMAIN:
                $domain = isset($groupData['base_url']['fields']['url_type']['subdomain'])?$groupData['base_url']['fields']['url_type']['subdomain']:'';
                $domain = trim(strtolower($domain));
                if(!$domain){
                    $vendor = $om->create('Vnecoms\Vendors\Model\Vendor')->load($vendorId);
                    $domain = strtolower($vendor->getVendorId());
                }
                $tmpVendor = $om->create('Vnecoms\Vendors\Model\Vendor')->loadByIdentifier($domain);
                if($tmpVendor->getId() && $tmpVendor->getId() != $vendorId){
                    throw new LocalizedException(__("The domain is already in used by another seller."));
                }
                $this->checkAndSaveDomain($vendorId, $domain, SourceUrl::URL_SUB_DOMAIN, $isSecure);
                break;
            case SourceUrl::URL_DOMAIN:
                $domain = isset($groupData['base_url']['fields']['url_type']['domain'])?$groupData['base_url']['fields']['url_type']['domain']:'';
                $domain = trim(strtolower($domain));
                if(!$domain) throw new LocalizedException(__("Please enter your domain"));
                $this->checkAndSaveDomain($vendorId, $domain, SourceUrl::URL_DOMAIN, $isSecure);
                break;
        }
        
        if($currentValue == SourceUrl::URL_SUB_FOLDER){
            /*Drop domain table.*/
            $domain = $om->create('Vnecoms\VendorsDomain\Model\Domain')
                    ->getResource()
                    ->removeDomainByVendorId($vendorId);
        }elseif(
            $currentValue != SourceUrl::URL_SUB_FOLDER &&
            $currentValue != $savedValue
        ) {
            $domain = $om->create('Vnecoms\VendorsDomain\Model\Domain')->load($vendorId, 'vendor_id');
        }
        

        return parent::beforeSave();
    }
    
    public function checkAndSaveDomain($vendorId, $value, $type, $isSecure = false){
        $om = ObjectManager::getInstance();
        $domainHelper = $om->get('Vnecoms\VendorsDomain\Helper\Data');
        $mainDomain = $domainHelper->getMainDomain();
        $domain = $type == SourceUrl::URL_SUB_DOMAIN?$value.'.'.$mainDomain:$value;
        
        /*Check if domain is exist*/
        $domainObj = $om->create('Vnecoms\VendorsDomain\Model\Domain')->load($domain, 'domain');
        if($domainObj->getId() && $domainObj->getVendorId() != $vendorId){
            throw new LocalizedException(__("The domain is already in used by another seller."));
        }
        
        $domainObj = $om->create('Vnecoms\VendorsDomain\Model\Domain')->load($vendorId, 'vendor_id');
        $oldValue = $domainObj->getValue();
        
        
        $domainObj->addData([
            'vendor_id' => $vendorId,
            'value' => $value,
            'domain' => $domain,
            'note' => null,
            'is_secure' => $isSecure,
            'status' => $oldValue == $value?$domainObj->getStatus():Domain::STATUS_PENDING,
        ])->setId($domainObj->getId())->save();
    }
}
