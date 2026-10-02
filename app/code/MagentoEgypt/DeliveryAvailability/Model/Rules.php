<?php
declare(strict_types=1);
namespace MagentoEgypt\DeliveryAvailability\Model;
class Rules
{
    public function evaluate(array $rules, string $country, int $city, int $locality, string $sku): string
    {
        $status='unconfigured'; $rank=['unconfigured'=>0,'green'=>1,'red'=>2,'blacklist'=>3];
        foreach ($rules as $rule) {
            if ($rule['country'] !== $country || ($rule['city_id'] && $rule['city_id'] !== $city)
                || ($rule['locality_id'] && $rule['locality_id'] !== $locality)
                || ($rule['sku'] !== '*' && $rule['sku'] !== $sku)) continue;
            if ($rank[$rule['status']] > $rank[$status]) $status=$rule['status'];
        }
        return $status;
    }
    public function parse(string $json): array
    {
        if ($json !== '' && !str_starts_with(ltrim($json), '[')) throw new \InvalidArgumentException('Rules must be a JSON array.');
        $rules=json_decode($json ?: '[]',true,512,JSON_THROW_ON_ERROR);
        if (!is_array($rules) || !array_is_list($rules) || count($rules)>1000) throw new \InvalidArgumentException('Rules must be an array with at most 1000 entries.');
        foreach ($rules as &$r) {
            if (!is_array($r) || !in_array($r['country']??'', ['EG','AE','SA','US'],true)
                || !in_array($r['status']??'', ['green','red','blacklist'],true)
                || !is_string($r['sku']??null) || trim($r['sku'])==='' || strlen($r['sku'])>64) throw new \InvalidArgumentException('Invalid delivery rule.');
            foreach (['city_id','locality_id'] as $key) {
                $r[$key]=$r[$key]??0;
                if (!is_int($r[$key]) || $r[$key]<0) throw new \InvalidArgumentException('Location IDs must be non-negative integers.');
            }
            if ($r['locality_id'] && !$r['city_id']) throw new \InvalidArgumentException('Locality requires a city.');
        }
        return $rules;
    }
}
