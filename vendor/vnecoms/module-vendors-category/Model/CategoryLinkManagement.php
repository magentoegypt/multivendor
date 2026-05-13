<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model;

/**
 * Class CategoryLinkManagement.
 */
class CategoryLinkManagement implements \Vnecoms\VendorsCategory\Api\CategoryLinkManagementInterface
{
    /**
     * @var \Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ResourceModel\Product
     */
    protected $productResource;

    /**
     * @var \Vnecoms\VendorsCategory\Api\CategoryLinkRepositoryInterface
     */
    protected $categoryLinkRepository;

    /**
     * @var \Vnecoms\VendorsCategory\Api\Data\CategoryProductLinkInterfaceFactory
     */
    protected $productLinkFactory;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry
     */
    protected $indexerRegistry;

    /**
     * CategoryLinkManagement constructor.
     *
     * @param \Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface              $categoryRepository
     * @param \Vnecoms\VendorsCategory\Api\Data\CategoryProductLinkInterfaceFactory $productLinkFactory
     */
    public function __construct(
        \Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface $categoryRepository,
        \Vnecoms\VendorsCategory\Api\Data\CategoryProductLinkInterfaceFactory $productLinkFactory
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->productLinkFactory = $productLinkFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssignedProducts($categoryId)
    {
        $category = $this->categoryRepository->get($categoryId);

        /** @var \Magento\Catalog\Model\ResourceModel\Product\Collection $products */
        $products = $category->getProductCollection();
        $products->addFieldToSelect('position');

        /** @var \Vnecoms\VendorsCategory\Api\Data\CategoryProductLinkInterface[] $links */
        $links = [];

        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($products->getItems() as $product) {
            /** @var \Vnecoms\VendorsCategory\Api\Data\CategoryProductLinkInterface $link */
            $link = $this->productLinkFactory->create();
            $link->setSku($product->getSku())
                ->setPosition($product->getData('cat_index_position'))
                ->setCategoryId($category->getId());
            $links[] = $link;
        }

        return $links;
    }

    /**
     * Assign product to given categories.
     *
     * @param string $productSku
     * @param \int[] $categoryIds
     *
     * @return bool
     */
    public function assignProductToCategories($productSku, array $categoryIds)
    {
        if (!is_array($categoryIds)) {
            $categoryIds = explode(',', $categoryIds);
        }
        $categoryIds = array_filter($categoryIds);
        $product = $this->getProductRepository()->get($productSku);
        $assignedCategories = $product->getData('ves_category_ids');
        if (!is_array($assignedCategories)) {
            $assignedCategories = explode(',', $assignedCategories);
        }
       // var_dump(array_diff($assignedCategories, $categoryIds));exit;

        foreach (array_diff($assignedCategories, $categoryIds) as $categoryId) {
            if(!$categoryId) continue;
            $this->getCategoryLinkRepository()->deleteByIds($categoryId, $productSku);
        }

        foreach (array_diff($categoryIds, $assignedCategories) as $categoryId) {
            if(!$categoryId) continue;
            /** @var \Vnecoms\VendorsCategory\Api\Data\CategoryProductLinkInterface $categoryProductLink */
            $categoryProductLink = \Magento\Framework\App\ObjectManager::getInstance()
                ->create('Vnecoms\VendorsCategory\Model\CategoryProductLink');
            $categoryProductLink->setSku($productSku);
            $categoryProductLink->setCategoryId($categoryId);
            $categoryProductLink->setPosition(0);
            $this->getCategoryLinkRepository()->save($categoryProductLink);
        }
//        $productCategoryIndexer = $this->getIndexerRegistry()->get(Indexer\Product\Category::INDEXER_ID);
//        if (!$productCategoryIndexer->isScheduled()) {
//            $productCategoryIndexer->reindexRow($product->getId());
//        }
        return true;
    }

    /**
     * Retrieve product repository instance.
     *
     * @return \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private function getProductRepository()
    {
        if (null === $this->productRepository) {
            $this->productRepository = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Magento\Catalog\Api\ProductRepositoryInterface');
        }

        return $this->productRepository;
    }

    /**
     * Retrieve product resource instance.
     *
     * @return \Magento\Catalog\Model\ResourceModel\Product
     */
    private function getProductResource()
    {
        if (null === $this->productResource) {
            $this->productResource = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Magento\Catalog\Model\ResourceModel\Product');
        }

        return $this->productResource;
    }

    /**
     * Retrieve category link repository instance.
     *
     * @return \Vnecoms\VendorsCategory\Api\CategoryLinkRepositoryInterface
     */
    private function getCategoryLinkRepository()
    {
        if (null === $this->categoryLinkRepository) {
            $this->categoryLinkRepository = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Vnecoms\VendorsCategory\Api\CategoryLinkRepositoryInterface');
        }

        return $this->categoryLinkRepository;
    }

    /**
     * Retrieve indexer registry instance.
     *
     * @return \Magento\Framework\Indexer\IndexerRegistry
     */
    private function getIndexerRegistry()
    {
        if (null === $this->indexerRegistry) {
            $this->indexerRegistry = \Magento\Framework\App\ObjectManager::getInstance()
                ->get('Magento\Framework\Indexer\IndexerRegistry');
        }

        return $this->indexerRegistry;
    }
}
