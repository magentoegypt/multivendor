<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsLayerNavigation\Model\Layer\VendorHomePage;

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

    /**
     * @param \Magento\Catalog\Model\Product\Visibility $productVisibility
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Magento\Framework\Registry $registry
    ) {
        $this->productVisibility = $productVisibility;
        $this->catalogConfig = $catalogConfig;
        $this->registry = $registry;
    }

    /**
     * Filter product collection.
     *
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @param \Magento\Catalog\Model\Category                         $category
     */
    public function filter(
        $collection,
        \Magento\Catalog\Model\Category $category
    ) {
        $collection
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addUrlRewrite($category->getId())
            ->setVisibility($this->productVisibility->getVisibleInCatalogIds());

        $vendor = $this->registry->registry('vendor');
        
        if ($vendor) {
            $om = \Magento\Framework\App\ObjectManager::getInstance();

            $collection->addAttributeToFilter('vendor_id', $vendor->getId());
            $collection->addAttributeToFilter('approval', array('in' => $om->create('Vnecoms\VendorsProduct\Helper\Data')->getAllowedApprovalStatus()));
        }
    }
}
