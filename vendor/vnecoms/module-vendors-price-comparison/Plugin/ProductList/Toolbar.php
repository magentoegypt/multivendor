<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPriceComparison\Plugin\ProductList;

class Toolbar
{
    /**
     * @param \Magento\Catalog\Block\Product\ProductList\Toolbar $subject
     * @param $result
     * @return int|null
     */
    public function afterGetTotalNum(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
        $result
    ) {
        return count($subject->getCollection());
    }

}
