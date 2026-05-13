<?php
/**
 * Copyright © Vnecoms, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsProduct\Plugin\Model\Product;

use Magento\Framework\Module\Manager as ModuleManager;
use Vnecoms\VendorsProduct\Helper\Data as VendorsProductHelper;

/**
 * Class TypePlugin
 */
class TypePlugin
{
    /**
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     * @var \Vnecoms\VendorsProduct\Helper\Data
     */
    private $vendorsProductHelper;

    public function __construct(
        ModuleManager $moduleManager,
        VendorsProductHelper $vendorsProductHelper
    ) {
        $this->moduleManager = $moduleManager;
        $this->vendorsProductHelper = $vendorsProductHelper;
    }

    /**
     * @param \Magento\Catalog\Model\Product\Type $subject
     * @param array $result
     * @return array
     */
    public function afterGetTypes(
        \Magento\Catalog\Model\Product\Type $subject,
        $result
    ) {
        if (!$this->moduleManager->isEnabled('Vnecoms_VendorsProduct')) {
            return $result;
        }
        $productTypesRestrict = $this->vendorsProductHelper->getProductTypeRestriction();
        foreach ($result as $productTypeKey => $productTypeConfig) {
            if (in_array($productTypeKey, $productTypesRestrict)) {
                unset($result[$productTypeKey]);
            }
        }
        return $result;
    }
}
