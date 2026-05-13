<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsPage\Model\Layer;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Layer\ContextInterface;
use Magento\Catalog\Model\Layer\StateFactory;

class VendorHomePage extends \Magento\Catalog\Model\Layer
{
    /**
     * @param ContextInterface $context
     * @param StateFactory $layerStateFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product $catalogProduct
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Registry $registry
     * @param CategoryRepositoryInterface $categoryRepository
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        StateFactory $layerStateFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Product $catalogProduct,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        CategoryRepositoryInterface $categoryRepository,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $layerStateFactory,
            $attributeCollectionFactory,
            $catalogProduct,
            $storeManager,
            $registry,
            $categoryRepository,
            $data
        );
    }
    
    /**
     * Retrieve current layer product collection
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getProductCollection()
    {
        $collection = $this->collectionProvider->getCollection($this->getCurrentCategory());
        $this->prepareProductCollection($collection);
        $vendor = $this->registry->registry('vendor');
        if (!$vendor) {
            return $collection;
        }
        
        $collection->addAttributeToFilter('vendor_id', $vendor->getId());
        $collection->addAttributeToFilter('approval', ['in' => \Magento\Framework\App\ObjectManager::getInstance()
        ->create('Vnecoms\VendorsProduct\Helper\Data')->getAllowedApprovalStatus()]);
        return $collection;
    }
    
    /**
     * Get layer state key
     *
     * @return string
     */
    public function getStateKey()
    {
        $vendorId = $this->registry->registry('vendor_id');
        
        if (!$this->_stateKey) {
            $this->_stateKey = $this->stateKeyGenerator->toString($vendorId);
        }
        return $this->_stateKey;
    }
}
