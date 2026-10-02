<?php
declare(strict_types=1);
namespace MagentoEgypt\DeliveryAvailability\Model;
class Availability
{
    public function __construct(private \Magento\Framework\App\Config\ScopeConfigInterface $config,
        private \MagentoEgypt\CityManager\Model\Directory $directory, private Rules $engine) {}
    public function rules(): array { return $this->engine->parse((string)$this->config->getValue('deliveryavailability/general/rules')); }
    public function location(string $country,int $region,int $city,int $locality,string $name=''): array
    { return $city ? $this->directory->resolveIds($country,$region,$city,$locality) : $this->directory->resolve($country,$region,$name); }
    public function check(array $location,string $sku): array
    {
        $status=$this->engine->evaluate($this->rules(),$location['country_id'],(int)$location['location_id'],(int)($location['locality']['location_id']??0),$sku);
        return ['sku'=>$sku,'coverage'=>$status,'blocked'=>in_array($status,['red','blacklist'],true),
            'warehouse_availability'=>'not_evaluated','shipping_quote'=>'checkout'];
    }
}
