<?php
namespace MagentoEgypt\CityManager\Api;
interface DirectoryInterface
{
    /**
     * @param string $country
     * @param string $level
     * @param int $parent
     * @param int $region
     * @return mixed[]
     */
    public function locations($country, $level='region', $parent=0, $region=0);
}
