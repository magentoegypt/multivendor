<?php
namespace MagentoEgypt\CityManager\Plugin;
class CustomerAddress
{
    public function __construct(private \MagentoEgypt\CityManager\Model\Directory $directory,private \Magento\Customer\Api\Data\RegionInterfaceFactory $regions, private \Magento\Framework\App\ResourceConnection $resource, private \MagentoEgypt\CityManager\Model\Settings $settings) {}
    public function beforeSave($subject, \Magento\Customer\Api\Data\AddressInterface $address) {
        if (!$this->settings->enabled()) return [$address];
        $country=(string)$address->getCountryId();
        if(!in_array($country,\MagentoEgypt\CityManager\Model\Directory::COUNTRIES,true)) return [$address];
        $ext=$address->getExtensionAttributes();
        $cityId=(int)($ext && $ext->getCmCityId() ? $ext->getCmCityId() : ($address->getCustomAttribute('cm_city_id')?->getValue() ?? 0));
        $localityId=(int)($ext && $ext->getCmLocalityId() ? $ext->getCmLocalityId() : ($address->getCustomAttribute('cm_locality_id')?->getValue() ?? 0));
        if ($address->getId()) {
            $db=$this->resource->getConnection();
            $old=$db->fetchRow($db->select()->from($this->resource->getTableName('customer_address_entity'),['country_id','city','region_id','cm_city_id','cm_locality_id'])->where('entity_id = ?', (int)$address->getId()));
            if ($old) {
                $sameGeography=$old['country_id']===$country && $old['city']===$address->getCity() && (int)$old['region_id']===(int)$address->getRegionId();
                $sameIds=(!$cityId || $cityId===(int)$old['cm_city_id']) && (!$localityId || $localityId===(int)$old['cm_locality_id']);
                // Contact-only edits must preserve historical/deactivated locations.
                if ($sameGeography && $sameIds) return [$address];
                // Older API clients carry persisted attributes while changing names.
                if (!$sameGeography && $sameIds) { $cityId=0; $localityId=0; }
            }
        }
        if($cityId) {
            $city=$this->directory->resolveIds($country,(int)$address->getRegionId(),$cityId,$localityId);
            $address->setCity($city['name_en'].(isset($city['locality'])?' / '.$city['locality']['name_en']:''));
            $address->setCustomAttribute('cm_city_id',$cityId);
            $address->setCustomAttribute('cm_locality_id',$country==='AE'?$localityId:null);
        } elseif($address->getCity()) {
            $city=$this->directory->resolve($country,(int)$address->getRegionId(),(string)$address->getCity());
            $address->setCity($city['name_en'].(isset($city['locality'])?' / '.$city['locality']['name_en']:''));
            $address->setCustomAttribute('cm_city_id',(int)$city['location_id']);
            $address->setCustomAttribute('cm_locality_id',isset($city['locality'])?(int)$city['locality']['location_id']:null);
        }
        if($country==='AE' && isset($city)) {
            $address->setRegionId((int)$city['region_id']);
            $region=$address->getRegion() ?: $this->regions->create();
            $region->setRegionId((int)$city['region_id']); $address->setRegion($region);
        }
        return [$address];
    }
}
