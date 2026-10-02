<?php
declare(strict_types=1);
namespace MagentoEgypt\DeliveryAvailability\Model;
class RulesConfig extends \Magento\Framework\App\Config\Value
{
    public function beforeSave()
    {
        try {
            $rules=(new Rules())->parse((string)$this->getValue());
            $db=$this->getResource()->getConnection();
            $table=$this->getResource()->getTable('me_city_location');
            foreach ($rules as $rule) {
                foreach (['city_id'=>'city','locality_id'=>'locality'] as $key=>$level) {
                    if (!$rule[$key]) continue;
                    $row=$db->fetchRow($db->select()->from($table)->where('location_id = ?', $rule[$key]));
                    if (!$row || !$row['is_active'] || $row['level']!==$level || $row['country_id']!==$rule['country']
                        || ($level==='locality' && (int)$row['parent_id']!==$rule['city_id'])) throw new \InvalidArgumentException('Rule references an invalid or inactive location.');
                }
            }
            $this->setValue(json_encode($rules,JSON_THROW_ON_ERROR));
        } catch (\Throwable $e) { throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage())); }
        return parent::beforeSave();
    }
}
