<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsProduct\Ui\DataProvider\Product\Form\Modifier;

use Magento\Framework\App\ObjectManager;
use Vnecoms\VendorsProduct\Helper\Data;

/**
 * Data provider for advanced inventory form
 */
class AdvancedInventory extends \Magento\CatalogInventory\Ui\DataProvider\Product\Form\Modifier\AdvancedInventory
{
    const STOCK_ATTRIBUTE = "quantity_and_stock_status";
    /**
     * @inheritdoc
     */
    public function modifyMeta(array $meta)
    {
        $ignoreAttribute = ObjectManager::getInstance()->get(Data::class)->getNotUsedVendorAttributes();
        if (in_array(self::STOCK_ATTRIBUTE, $ignoreAttribute)) return $meta;
        return parent::modifyMeta($meta);
    }
}
