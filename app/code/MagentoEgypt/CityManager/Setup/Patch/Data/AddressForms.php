<?php
namespace MagentoEgypt\CityManager\Setup\Patch\Data;
class AddressForms implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    public function __construct(private \Magento\Customer\Setup\CustomerSetupFactory $factory, private \Magento\Framework\Setup\ModuleDataSetupInterface $setup) {}
    public function apply() {
        $s=$this->factory->create(['setup'=>$this->setup]);
        foreach(['cm_city_id','cm_locality_id'] as $code) {
            $attribute=$s->getEavConfig()->getAttribute('customer_address',$code);
            $attribute->setData('used_in_forms',['customer_address_edit','customer_register_address','adminhtml_customer_address'])->save();
        }
        return $this;
    }
    public static function getDependencies(){return [AddressAttributes::class];}
    public function getAliases(){return [];}
}
