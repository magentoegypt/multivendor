<?php
namespace MagentoEgypt\VendorExtend\Plugin\Model\TaxClass;

class Product
{
    public function afterGetAllOptions(
        \Magento\Tax\Model\TaxClass\Source\Product $subject,
        array $result
    ) {
        foreach ($result as &$option) {
            if ($option['value'] !== '0') {
                $option['label'] = __($option['label']);
            }
        }
        return $result;
    }
}
