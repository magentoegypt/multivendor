<?php
namespace MagentoEgypt\VendorExtend\Plugin\Model;

class PageLayout
{
    public function afterToOptionArray($subject, $result) {
        foreach ($result as &$option) {
            if ($option['value'] !== '') {
                $option['label'] = __($option['label']);
            }
        }
        return $result;
    }
}
