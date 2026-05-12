<?php
namespace MagentoEgypt\BundleExtend\Plugin\Model;

class SetSuperConfigurableProduct
{
    public function beforePrepareForCart($subject, \Magento\Framework\DataObject $buyRequest, $product)
    {
        $superAttribute = $buyRequest->getSuperAttribute();
        if (is_array($superAttribute) && !empty($superAttribute) && isset($superAttribute[$product->getId()])) {
            $superAttribute = $superAttribute[$product->getId()];
            $superAttribute = is_array($superAttribute) ? array_filter($superAttribute, 'intval') : [];
            $buyRequest->setSuperAttribute($superAttribute);
        }
        return [$buyRequest, $product];
    }
}   