<?php

namespace Vnecoms\VendorsLayerNavigation\Model\Layer\VendorHomePage;

use Magento\Catalog\Model\Layer\ItemCollectionProviderInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class ItemCollectionProvider implements ItemCollectionProviderInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $_productCollection;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct
    (
        CollectionFactory $collectionFactory
    )
    {
        $this->_productCollectionFactory = $collectionFactory;
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    public function getCollection(\Magento\Catalog\Model\Category $category)
    {
        $collection = $this->_productCollectionFactory->create();
        return $collection;
    }
}
