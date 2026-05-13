<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vnecoms\VendorsCategory\Model\Layer\Filter\DataProvider;

use Vnecoms\VendorsCategory\Model\Category as CategoryModel;
use Vnecoms\VendorsCategory\Model\CategoryFactory as CategoryModelFactory;
use Vnecoms\VendorsLayerNavigation\Model\Layer\VendorHomePage as VendorHomePage;
use Magento\Framework\Registry;

class Category
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var CategoryModel
     */
    private $category;

    /**
     * @var int
     */
    private $categoryId;

    /**
     * @var bool
     */
    private $isApplied = false;

    /**
     * @var VendorHomePage
     */
    private $layer;

    /**
     * @var CategoryModelFactory
     */
    private $categoryFactory;

    /**
     * @param Registry $coreRegistry
     * @param CategoryModelFactory $categoryFactory
     * @param VendorHomePage $layer
     * @internal param $data
     */
    public function __construct(
        Registry $coreRegistry,
        CategoryModelFactory $categoryFactory,
        VendorHomePage $layer
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->layer = $layer;
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * Validate category for using as filter
     *
     * @return mixed
     */
    public function isValid()
    {
        $category = $this->getCategory();
        if ($category->getId()) {
            while ($category->getLevel() != 0) {
                if (!$category->getIsActive()) {
                    return false;
                }
                $category = $category->getParentCategory();
            }

            return true;
        }

        return false;
    }

    /**
     * @param int $categoryId
     * @return $this
     */
    public function setCategoryId($categoryId)
    {
        $this->isApplied = true;
        $this->category = null;
        $this->categoryId = $categoryId;

        return $this;
    }

    /**
     * @return boolean
     */
    private function isApplied()
    {
        return $this->isApplied;
    }

    /**
     * Get selected category object
     *
     * @return CategoryModel
     */
    public function getCategory()
    {
        if ($this->category === null) {
            /** @var CategoryModel|null $category */
            $category = null;
            if ($this->categoryId !== null) {
                $category = $this->categoryFactory->create()
                    ->load($this->categoryId);
            }

            if ($category === null || !$category->getId()) {
                $category = $this->getLayer()
                    ->getCurrentVendorCategory();
            }

            $this->coreRegistry->register('v_current_category_filter', $category, true);
            $this->category = $category;
        }
        //var_dump($this->category->getData());die();

        return $this->category;
    }

    /**
     * Get filter value for reset current filter state
     *
     * @return mixed|null
     */
    public function getResetValue()
    {
        if ($this->isApplied()) {
            /**
             * Revert path ids
             */
            $category = $this->getCategory();
            $pathIds = array_reverse($category->getPathIds());
            $curCategoryId = $this->getLayer()
                ->getCurrentVendorCategory()
                ->getId();
            if (isset($pathIds[1]) && $pathIds[1] != $curCategoryId) {
                return $pathIds[1];
            }
        }

        return null;
    }

    /**
     * @return VendorHomePage
     */
    private function getLayer()
    {
        return $this->layer;
    }
}
