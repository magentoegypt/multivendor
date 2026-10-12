<?php
namespace MagentoEgypt\CityManager\Plugin;
class OrderAddress
{
    public function afterConvert($subject,$result,$address,$data=[]) {foreach(['cm_city_id','cm_locality_id'] as $key) $result->setData($key,$address->getData($key));return $result;}
}
