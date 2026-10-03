<?php
namespace MagentoEgypt\CityManager\Setup\Patch\Data;
class AddressAttributes implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    public function __construct(private \Magento\Customer\Setup\CustomerSetupFactory $factory,private \Magento\Framework\Setup\ModuleDataSetupInterface $setup) {}
    public function apply() {
        $s=$this->factory->create(['setup'=>$this->setup]);
        foreach(['cm_city_id'=>'City Manager City ID','cm_locality_id'=>'City Manager Locality ID'] as $code=>$label) {
            $s->addAttribute('customer_address',$code,['type'=>'static','label'=>$label,'input'=>'hidden','required'=>false,'visible'=>false,'system'=>false,'user_defined'=>true]);
        }
        return $this;
    }
    public static function getDependencies(){return [];}
    public function getAliases(){return [];}
}
