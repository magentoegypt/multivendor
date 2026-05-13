<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model\Rewrite;

use Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface;
use Vnecoms\VendorsCategory\Model\Category;

class CategoryUrlPathGenerator
{
    /**
     * Minimal category level that can be considered for generate path.
     */
    const MINIMAL_CATEGORY_LEVEL_FOR_PROCESSING = 2;

    /**
     * XML path for category url suffix.
     */
    const XML_PATH_CATEGORY_URL_SUFFIX = 'catalog/seo/category_url_suffix';

    /**
     * Cache for category rewrite suffix.
     *
     * @var array
     */
    protected $categoryUrlSuffix = [];

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    /** @var \Magento\Store\Model\StoreManagerInterface */
    protected $storeManager;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @param \Magento\Store\Model\StoreManagerInterface         $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param CategoryRepositoryInterface                        $categoryRepository
     */
    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Build category URL path.
     *
     * @
     *
     * @param \Vnecoms\VendorsCategory\Api\Data\CategoryInterface|\Magento\Framework\Model\AbstractModel $category
     * @param $isIndexMode boolean
     *
     * @return string
     */
    public function getUrlPath($category, $isIndexMode = false)
    {
        if ($category->getId() == $category->getRootId($category->getVendorId())) {
            return '';
        }
        $baseUrlKey = $this->scopeConfig->getValue(
            'vendors/vendorspage/url_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $category->getStoreId()
        );
        $vendorId = $category->getVendorId();
        $vendorId = \Magento\Framework\App\ObjectManager::getInstance()
            ->create('Vnecoms\Vendors\Model\Vendor')->load($vendorId)->getVendorId();

        $storeId = $category->getStoreId();

//        $path = $category->getUrlPath();
//        if (!$isIndexMode && $path !== null && !$category->dataHasChangedFor('url_key') && !$category->dataHasChangedFor('parent_id')) {
//            return $path;
//        }
        $path = $category->getUrlKey();
        $prefixPath = '';
//        if ($path === false) {
//            return $category->getUrlPath();
//        } else {
//            if ($baseUrlKey) {
//                $prefixPath .= $baseUrlKey.'/'.$vendorId;
//            } else {
//                $prefixPath .= $vendorId;
//            }
//        }
        //generate url path for parent
        if ($this->isNeedToGenerateUrlPathForParent($category)) {
            $parentPath = $this->getUrlPath(
                $this->categoryRepository->get($category->getParentId())
            );
            $path = $parentPath === '' ? $path : $parentPath.'/'.$path;
        }

        //set prefix for vendor
        if ($category->getLevel() == '1') {
            $path = $prefixPath.$path;
        }

      //  var_dump($path);
        return $path;
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     *
     * @return bool
     */
    protected function isNeedToGenerateUrlPathForParent($category)
    {
        return $category->isObjectNew() || $category->getLevel() >= self::MINIMAL_CATEGORY_LEVEL_FOR_PROCESSING;
    }

    /**
     * @param $category
     * @param null $storeId
     * @param bool $isIndexMode
     * @param bool $withSuffix
     * @return string
     */
    public function getUrlPathWithSuffix($category, $storeId = null, $isIndexMode = false, $withSuffix=true)
    {
        if ($storeId === null) {
            $storeId = $category->getStoreId();
        }

        return ($withSuffix) ? $this->getUrlPath($category, $isIndexMode).$this->getCategoryUrlSuffix($storeId):
            $this->getUrlPath($category, $isIndexMode);
    }

    /**
     * Retrieve category rewrite suffix for store (regular is .html).
     *
     * @param int $storeId
     *
     * @return string
     */
    public function getCategoryUrlSuffix($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }
        if (!isset($this->categoryUrlSuffix[$storeId])) {
            $this->categoryUrlSuffix[$storeId] = $this->scopeConfig->getValue(
                self::XML_PATH_CATEGORY_URL_SUFFIX,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            );
        }

        return $this->categoryUrlSuffix[$storeId];
    }

    /**
     * Get canonical category url.
     *
     * @param \Magento\Catalog\Model\Category $category
     *
     * @return string
     */
    public function getCanonicalUrlPath($category)
    {
        $baseUrlKey = $this->scopeConfig->getValue(
            'vendors/vendorspage/url_key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $category->getStoreId()
        );
        $vendorId = \Magento\Framework\App\ObjectManager::getInstance()
        ->create('Vnecoms\Vendors\Model\Vendor')->load($category->getVendorId())->getVendorId();
        if (!$baseUrlKey) {
            return $vendorId.'/category/view/id/'.$category->getId();
        }

        return $baseUrlKey.'/'.$vendorId.'/category/view/id/'.$category->getId();
    }

    /**
     * Generate category url key.
     *
     * @param \Vnecoms\VendorsCategory\Model\Category $category
     *
     * @return string
     */
    public function getUrlKey($category)
    {
        $urlKey = $category->getUrlKey();

        return $category->formatUrlKey($urlKey === '' || $urlKey === null ? $category->getName() : $urlKey);
    }
}
