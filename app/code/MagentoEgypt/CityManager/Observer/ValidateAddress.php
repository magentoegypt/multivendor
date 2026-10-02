<?php
namespace MagentoEgypt\CityManager\Observer;
class ValidateAddress implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(private \MagentoEgypt\CityManager\Model\Directory $directory, private \MagentoEgypt\CityManager\Model\Settings $settings) {}
    public function execute(\Magento\Framework\Event\Observer $observer) {
        if (!$this->settings->enabled()) return;
        $auxiliary=$observer->getEvent()->getData('object');
        if($auxiliary) {
            if($auxiliary instanceof \MGS\StoreLocator\Model\Store) {
                $country=(string)$auxiliary->getData('country');
                if(!in_array($country,\MagentoEgypt\CityManager\Model\Directory::COUNTRIES,true))return;
                if($auxiliary->getId() && !$auxiliary->dataHasChangedFor('country') && !$auxiliary->dataHasChangedFor('state') && !$auxiliary->dataHasChangedFor('city'))return;
                $regions=$this->directory->options($country,'region');$state=(string)$auxiliary->getData('state');$regionId=0;
                foreach($regions as $region)if(in_array($state,[$region['name_en'],$region['name_ar']],true))$regionId=(int)$region['region_id'];
                $city=$this->directory->resolve($country,$regionId,(string)$auxiliary->getData('city'));
                foreach($regions as $region)if((int)$region['region_id']===(int)$city['region_id'])$auxiliary->setData('state',$region['name_en']);
                $auxiliary->setData('city',$city['name_en'].(isset($city['locality'])?' / '.$city['locality']['name_en']:''));return;
            }
            if(!$auxiliary instanceof \Vnecoms\RMA\Model\Address)return;
        }
        $object=$observer->getEvent()->getData('data_object') ?: $observer->getEvent()->getData('vendor') ?: $observer->getEvent()->getData('customer_address') ?: $observer->getEvent()->getData('quote_address') ?: $observer->getEvent()->getData('address');
        $object=$object?:$auxiliary;
        if(!$object) return;
        $country=(string)$object->getData('country_id'); $city=trim((string)$object->getData('city'));
        if(!in_array($country,\MagentoEgypt\CityManager\Model\Directory::COUNTRIES,true)||$city==='') return;
        if ($object instanceof \Magento\Customer\Model\Address && $this->directory->unchangedCustomerAddress($object)) return;
        // Leave historical/unchanged legacy records intact; validate new or edited geography.
        if($object->getId() && !$object->dataHasChangedFor('city') && !$object->dataHasChangedFor('country_id') && !$object->dataHasChangedFor('region_id') && !$object->dataHasChangedFor('cm_city_id') && !$object->dataHasChangedFor('cm_locality_id')) return;
        $cityId=(int)$object->getData('cm_city_id');
        // Legacy clients update names but carry old persisted IDs on loaded models.
        if ($object->getId() && !$object->dataHasChangedFor('cm_city_id') && !$object->dataHasChangedFor('cm_locality_id') && ($object->dataHasChangedFor('city') || $object->dataHasChangedFor('country_id') || $object->dataHasChangedFor('region_id'))) $cityId=0;
        $row=$cityId?$this->directory->resolveIds($country,(int)$object->getData('region_id'),$cityId,(int)$object->getData('cm_locality_id')):$this->directory->resolve($country,(int)$object->getData('region_id'),$city);
        if($cityId) {
            $canonical=$row['name_en'].(isset($row['locality'])?' / '.$row['locality']['name_en']:'');
            if($canonical!==$city) {
                // Stored IDs can be stale when older clients edit the text fields.
                $row=$this->directory->resolve($country,(int)$object->getData('region_id'),$city);
            }
        }
        $object->setData('region_id',(int)$row['region_id']);
        $object->setData('cm_city_id',(int)$row['location_id']);
        $object->setData('cm_locality_id',isset($row['locality'])?(int)$row['locality']['location_id']:null);
    }
}
