<?php
namespace MagentoEgypt\CityManager\Setup\Patch\Data;
class AddressAttributeSet implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    public function __construct(private \Magento\Customer\Setup\CustomerSetupFactory $factory, private \Magento\Framework\Setup\ModuleDataSetupInterface $setup) {}
    public function apply() {
        $s=$this->factory->create(['setup'=>$this->setup]);
        $type=$s->getEavConfig()->getEntityType('customer_address');
        $set=(int)$type->getDefaultAttributeSetId();
        $group=(int)$s->getDefaultAttributeGroupId($type->getId(),$set);
        foreach(['cm_city_id','cm_locality_id'] as $code) {
            $s->addAttributeToSet('customer_address',$set,$group,$code);
        }
        $s->getEavConfig()->clear();
        return $this;
    }
    public static function getDependencies(){return [AddressForms::class];}
    public function getAliases(){return [];}
}
