<?php

namespace Vnecoms\VendorsProduct\Controller\Vendors\Product\Initialization;

class Helper extends \Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper
{
    public function initialize(\Magento\Catalog\Model\Product $product){
        
        return $product;
    }
}
