<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model\Rewrite;

use Vnecoms\VendorsCategory\Model\Category;

class ChildrenUrlRewriteGenerator
{
    /** @var \Vnecoms\VendorsCategory\Model\Rewrite\ChildrenCategoriesProvider */
    protected $childrenCategoriesProvider;

    /** @var \Vnecoms\VendorsCategory\Model\Rewrite\CategoryUrlRewriteGeneratorFactory */
    protected $categoryUrlRewriteGeneratorFactory;

    /**
     * @param \Vnecoms\VendorsCategory\Model\Rewrite\ChildrenCategoriesProvider         $childrenCategoriesProvider
     * @param \Vnecoms\VendorsCategory\Model\Rewrite\CategoryUrlRewriteGeneratorFactory $categoryUrlRewriteGeneratorFactory
     */
    public function __construct(
        ChildrenCategoriesProvider $childrenCategoriesProvider,
        CategoryUrlRewriteGeneratorFactory $categoryUrlRewriteGeneratorFactory
    ) {
        $this->childrenCategoriesProvider = $childrenCategoriesProvider;
        $this->categoryUrlRewriteGeneratorFactory = $categoryUrlRewriteGeneratorFactory;
    }

    /**
     * Generate list of children urls.
     *
     * @param int                                     $storeId
     * @param \Vnecoms\VendorsCategory\Model\Category $category
     *
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite[]
     */
    public function generate($storeId, Category $category, $isIndexMode)
    {
        $urls = [];
        foreach ($this->childrenCategoriesProvider->getChildren($category) as $childCategory) {
            $childCategory->setStoreId($storeId);
            $childCategory->setData('save_rewrites_history', $category->getData('save_rewrites_history'));
            $urls = array_merge(
                $urls,
                $this->categoryUrlRewriteGeneratorFactory->create()->generate($childCategory)
            );
        }

        return $urls;
    }
}
