<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model;

class CategoryManagement implements \Vnecoms\VendorsCategory\Api\CategoryManagementInterface
{
    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var \Vnecoms\VendorsCategory\Model\Category\Tree
     */
    protected $categoryTree;

    /**
     * @var \Magento\Framework\App\ScopeResolverInterface
     */
    private $scopeResolver;

    /**
     * @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory
     */
    private $categoriesFactory;

    /**
     * @var \Vnecoms\VendorsApi\Helper\Data
     */
    protected $helperApi;

    /**
     * CategoryManagement constructor.
     * @param \Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface $categoryRepository
     * @param Category\Tree $categoryTree
     * @param ResourceModel\Category\CollectionFactory $categoriesFactory
     * @param \Vnecoms\VendorsApi\Helper\Data $helperApi
     */
    public function __construct(
        \Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface $categoryRepository,
        \Vnecoms\VendorsCategory\Model\Category\Tree $categoryTree,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory $categoriesFactory,
        \Vnecoms\VendorsApi\Helper\Data $helperApi
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryTree = $categoryTree;
        $this->categoriesFactory = $categoriesFactory;
        $this->helperApi = $helperApi;
    }

    /**
     * @param int|null $customerId
     * @param null $rootCategoryId
     * @param null $depth
     * @return \Vnecoms\VendorsCategory\Api\Data\CategoryTreeInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTree($customerId, $rootCategoryId = null, $depth = null)
    {
        $customer   = $this->helperApi->getCustomer($customerId);
        $vendorModel     = $this->helperApi->getVendorByCustomer($customer);
        $category = null;
        if ($rootCategoryId !== null) {
            /** @var \Vnecoms\VendorsCategory\Model\Category $category */
            $category = $this->categoryRepository->get($rootCategoryId);
        }
        $rootNode = $this->categoryTree->setVendor($vendorModel)->getRootNode($category);
        $result = $this->categoryTree->getTree($rootNode, $depth);

        return $result;
    }

    /**
     * Get top level hidden root category.
     *
     * @return \Vnecoms\VendorsCategory\Model\Category
     */
    private function getTopLevelCategory()
    {
        $categoriesCollection = $this->categoriesFactory->create();

        return $categoriesCollection->addFilter('level', ['eq' => 0])->getFirstItem();
    }

    /**
     * {@inheritdoc}
     */
    public function move($categoryId, $parentId, $afterId = null)
    {
        $model = $this->categoryRepository->get($categoryId);
        $parentCategory = $this->categoryRepository->get($parentId);

        if ($parentCategory->hasChildren()) {
            $parentChildren = $parentCategory->getChildren();
            $categoryIds = explode(',', $parentChildren);
            $lastId = array_pop($categoryIds);
            $afterId = ($afterId === null || $afterId > $lastId) ? $lastId : $afterId;
        }

        if (strpos($parentCategory->getPath(), $model->getPath()) === 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Operation do not allow to move a parent category to any of children category')
            );
        }
        try {
            $model->move($parentId, $afterId);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Could not move category'));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getCount()
    {
        $categories = $this->categoriesFactory->create();
        /* @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category\Collection $categories */
        $categories->addAttributeToFilter('parent_id', ['gt' => 0]);

        return $categories->getSize();
    }
}
