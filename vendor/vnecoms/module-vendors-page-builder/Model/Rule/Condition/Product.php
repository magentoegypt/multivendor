<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * CatalogWidget Rule Product Condition data model
 */
namespace Vnecoms\VendorsPageBuilder\Model\Rule\Condition;

/**
 * Rule product condition data model
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Product extends \Magento\CatalogWidget\Model\Rule\Condition\Product
{
    /**
     * Retrieve value element chooser URL
     *
     * @return string
     */
    public function getValueElementChooserUrl()
    {
        $url = false;
        switch ($this->getAttribute()) {
            case 'sku':
            case 'category_ids':
                $url = 'catalog_rule/promo_widget/chooser/attribute/' . $this->getAttribute();
                if ($this->getJsFormObject()) {
                    $url .= '/form/' . $this->getJsFormObject();
                }
                break;
            default:
                break;
        }
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $vendorUrl = $objectManager->create('\Vnecoms\Vendors\Model\Url');
        return $url !== false ? $vendorUrl->getUrl($url) : '';
    }
}
