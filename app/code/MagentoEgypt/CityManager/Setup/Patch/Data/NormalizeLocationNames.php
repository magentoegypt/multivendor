<?php
namespace MagentoEgypt\CityManager\Setup\Patch\Data;
class NormalizeLocationNames implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    public function __construct(private \MagentoEgypt\CityManager\Model\Directory $directory, private \Magento\Framework\App\ResourceConnection $resource) {}
    public function apply() {
        $db=$this->resource->getConnection();
        foreach($db->fetchAll($db->select()->from($this->directory->table())->where('level IN (?)',['city','locality'])) as $row) {
            $changed=false;
            foreach(['name_en','name_ar'] as $key) { $name=strtr((string)($row[$key]??''),['‘'=>"'",'`'=>"'"]); if($name!==$row[$key]) { $row[$key]=$name; $changed=true; } }
            if($changed) $this->directory->save($row,0);
        }
        return $this;
    }
    public static function getDependencies(){return [AddressAttributeSet::class];}
    public function getAliases(){return [];}
}
