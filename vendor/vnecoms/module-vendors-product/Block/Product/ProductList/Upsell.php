<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsProduct\Block\Product\ProductList;

use Magento\Catalog\Model\Product;

/**
 * Catalog product related items block
 *
 * @api
 * @SuppressWarnings(PHPMD.LongVariable)
 * @since 100.0.2
 */
class Upsell extends \Magento\Catalog\Block\Product\ProductList\Upsell
{
    /**
     * Prepare data
     *
     * @return $this
     */
    protected function _prepareData()
    {
        $product = $this->getProduct();
        /* @var $product Product */
        $this->_itemCollection = $product->getUpSellProductCollection()->setPositionOrder()->addStoreFilter();
        if ($this->moduleManager->isEnabled('Magento_Checkout')) {
            $this->_addProductAttributesAndPrices($this->_itemCollection);
        }
        $this->_itemCollection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
        $object_manager = \Magento\Framework\App\ObjectManager::getInstance();
        $vendorHelper = $object_manager->get('Vnecoms\Vendors\Helper\Data');
        $notActiveVendorIds = $vendorHelper->getNotActiveVendorIds();

        $productHelper = $object_manager->get('Vnecoms\VendorsProduct\Helper\Data');

        if ($this->_itemCollection->isEnabledFlat()) {
            $this->_itemCollection->getSelect()->where('approval IN (?)', $productHelper->getAllowedApprovalStatus());
            if (sizeof($notActiveVendorIds)) {
                $this->_itemCollection->getSelect()->where('vendor_id NOT IN('.implode(",", $notActiveVendorIds).')');
            }
        } else {
            $this->_itemCollection->addAttributeToFilter('approval', ['in' => $productHelper->getAllowedApprovalStatus()]);
            if (sizeof($notActiveVendorIds)) {
                $this->_itemCollection->addAttributeToFilter('vendor_id', ['nin' => $notActiveVendorIds]);
            }
        }
        $this->_itemCollection->load();

        /**
         * Updating collection with desired items
         */
        $this->_eventManager->dispatch(
            'catalog_product_upsell',
            ['product' => $product, 'collection' => $this->_itemCollection, 'limit' => null]
        );

        foreach ($this->_itemCollection as $product) {
            $product->setDoNotUseCategoryId(true);
        }

        return $this;
    }
}
