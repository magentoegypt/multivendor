<?php
namespace Vnecoms\VendorsSms\Model\Config\Backend;

class Credit extends \Magento\Framework\App\Config\Value
{
    /**
     * @return $this
     */
    public function beforeSave()
    {
        if (is_array($this->getValue())) {
            $data = [];
            foreach ($this->getValue() as $value) {
                if (isset($value['delete']) && $value['delete']) {
                    continue;
                }
                $credit = (int)$value['credit'];
                $data[$credit] = [
                    'credit' => $credit,
                    'price' => $value['price'],
                ];
            }

            ksort($data);
            $this->setValue(serialize($data));
        }

        return parent::beforeSave();
    }
}
