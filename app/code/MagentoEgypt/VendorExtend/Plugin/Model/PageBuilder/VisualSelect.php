<?php 
namespace MagentoEgypt\VendorExtend\Plugin\Model\PageBuilder;

class VisualSelect
{
    public function afterToOptionArray(\Magento\PageBuilder\Model\Source\VisualSelect $subject, $options) {
        if(count($options)>0) {
            foreach ($options as $optionKey => & $optionValue) {
                if (isset($optionValue['icon'])) {
                    $optionValue['icon'] = str_replace('vendors/Vnecoms/vendor','adminhtml/Magento/backend',$optionValue['icon']);
                }
            }
        }
        return $options;
    }
}