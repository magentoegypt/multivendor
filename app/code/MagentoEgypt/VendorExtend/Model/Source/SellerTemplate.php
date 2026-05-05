<?php

namespace MagentoEgypt\VendorExtend\Model\Source;

class SellerTemplate implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray()
    {
        $options = array(
            array(
                'label' => __('Default Template'),
                'value' => 'MagentoEgypt_VendorExtend::widget/sellerlist.phtml'
            )
        );
        return $options;
    }
}