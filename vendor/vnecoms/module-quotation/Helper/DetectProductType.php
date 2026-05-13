<?php

namespace Vnecoms\Quotation\Helper;

/**
 * Helper class help detect product allow quote or not
 *
 * Class DetectProductType
 * @package Vnecoms\Quotation\Helper
 */
class DetectProductType
{
    public function productIsAllowQuoteMode(\Magento\Catalog\Model\Product $product)
    {
        return (bool) $product->getData('ves_enable_quote');
    }

    public function productIsAllowOrderMode(\Magento\Catalog\Model\Product $product)
    {
        return (bool) $product->getData('ves_enable_order');
    }
}