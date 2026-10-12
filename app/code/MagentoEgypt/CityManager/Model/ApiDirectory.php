<?php
namespace MagentoEgypt\CityManager\Model;
class ApiDirectory implements \MagentoEgypt\CityManager\Api\DirectoryInterface
{
    public function __construct(private Directory $directory) {}
    public function locations($country,$level='region',$parent=0,$region=0) {return $this->directory->options(strtoupper($country),$level,(int)$parent,(int)$region);}
}
