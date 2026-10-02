<?php
namespace MagentoEgypt\CityManager\Observer;
class ValidateAddress implements \Magento\Framework\Event\ObserverInterface
{
    public function __construct(private \MagentoEgypt\CityManager\Model\Directory $directory, private \MagentoEgypt\CityManager\Model\Settings $settings) {}
    public function execute(\Magento\Framework\Event\Observer $observer) {
        if (!$this->settings->enabled()) return;
        $object=$observer->getEvent()->getData('data_object') ?: $observer->getEvent()->getData('vendor') ?: $observer->getEvent()->getData('customer_address') ?: $observer->getEvent()->getData('quote_address') ?: $observer->getEvent()->getData('address');
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
