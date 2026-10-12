<?php
namespace MagentoEgypt\CityManager\Plugin;
class QuoteAddress
{
    public function __construct(private \MagentoEgypt\CityManager\Model\Directory $directory, private \MagentoEgypt\CityManager\Model\Settings $settings) {}
    public function normalize($address): void {
        if (!$this->settings->enabled()) return;
        if(!$address || !in_array($address->getCountryId(),\MagentoEgypt\CityManager\Model\Directory::COUNTRIES,true)) return;
        $ext=$address->getExtensionAttributes();
        $custom=$address->getCustomAttributes() ?: [];
        $ids=[];
        foreach($custom as $key=>$attr){
            if(is_object($attr) && method_exists($attr,'getAttributeCode')) $ids[$attr->getAttributeCode()]=$attr->getValue();
            elseif(is_array($attr) && isset($attr['attribute_code'])) $ids[$attr['attribute_code']]=$attr['value']??null;
            elseif(is_string($key)) $ids[$key]=$attr;
        }
        $cityId=(int)($ext && $ext->getCmCityId() ? $ext->getCmCityId() : ($ids['cm_city_id']??0));
        $localityId=(int)($ext && $ext->getCmLocalityId() ? $ext->getCmLocalityId() : ($ids['cm_locality_id']??0));
        if(!$cityId) return;
        $city=$this->directory->resolveIds((string)$address->getCountryId(),(int)$address->getRegionId(),$cityId,$localityId);
        $address->setCity($city['name_en'].(isset($city['locality'])?' / '.$city['locality']['name_en']:''));
        $address->setRegionId((int)$city['region_id']);
        $address->setData('cm_city_id',$cityId)->setData('cm_locality_id',$address->getCountryId()==='AE'?$localityId:null);
    }
    public function beforeSaveAddressInformation($subject,$cartId,$addressInformation) {
        $this->normalize($addressInformation->getShippingAddress());
        $this->normalize($addressInformation->getBillingAddress());
        return [$cartId,$addressInformation];
    }
    public function beforeAssign($subject,$cartId,$address,...$rest) {
        $this->normalize($address);
        return array_merge([$cartId,$address],$rest);
    }
}
