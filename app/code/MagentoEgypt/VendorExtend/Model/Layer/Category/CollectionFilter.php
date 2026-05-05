<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEgypt\VendorExtend\Model\Layer\Category;

use Magento\Catalog\Model\Layer\CollectionFilterInterface;

class CollectionFilter implements CollectionFilterInterface
{
    /**
     * Catalog product visibility.
     *
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $productVisibility;

    /**
     * Catalog config.
     *
     * @var \Magento\Catalog\Model\Config
     */
    protected $catalogConfig;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    protected $setup;
    /**
     * CollectionFilter constructor.
     *
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\Catalog\Model\Config             $catalogConfig
     */
    public function __construct(
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Framework\Registry $registry,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection $setup
    ) {
        $this->productVisibility = $productVisibility;
        $this->catalogConfig = $catalogConfig;
        $this->registry = $registry;
        $this->setup = $setup;
    }

    /**
     * Filter product collection
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param \Magento\Catalog\Model\Category $category
     * @return void
     */
    public function filter(
        $collection,
        \Magento\Catalog\Model\Category $category
    ) {
        $vendorCategory = $this->registry->registry('current_vendor_category');

        $collection
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite($category->getId())
            ->addAttributeToFilter('status', 1)
            ->setVisibility($this->productVisibility->getVisibleInCatalogIds());

        $vendor = $this->registry->registry('vendor');
        if ($vendor) {
            $collection->addAttributeToFilter('vendor_id', $vendor->getId());
            $collection->addAttributeToFilter('approval', \Vnecoms\VendorsProduct\Model\Source\Approval::STATUS_APPROVED);
        }
        

        if ($vendorCategory != null) {
            $collection->joinField(
                'position',
                $this->setup->getTable('ves_vendorscategory_category_product'),
                'position',
                'product_id=entity_id',
                'at_position.category_id='.(int) $vendorCategory->getId(),
                'left'
            )->getSelect()->where('at_position.category_id = ?', $vendorCategory->getId());
        }
    }
}
