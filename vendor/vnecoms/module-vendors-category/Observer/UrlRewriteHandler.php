<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Observer;

use Vnecoms\VendorsCategory\Model\Category;
use Vnecoms\VendorsCategory\Model\Rewrite\CategoryUrlRewriteGenerator;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class UrlRewriteHandler
{
    /** @var \Vnecoms\VendorsCategory\Model\Rewrite\ChildrenCategoriesProvider */
    protected $childrenCategoriesProvider;

    /** @var CategoryUrlRewriteGenerator */
    protected $categoryUrlRewriteGenerator;

    /** @var UrlPersistInterface */
    protected $urlPersist;

    /** @var array */
    protected $isSkippedProduct;

    /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory */
    protected $productCollectionFactory;

    /**
     * @param \Vnecoms\VendorsCategory\Model\Rewrite\ChildrenCategoriesProvider $childrenCategoriesProvider
     * @param CategoryUrlRewriteGenerator                                       $categoryUrlRewriteGenerator
     * @param UrlPersistInterface                                               $urlPersist
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory    $productCollectionFactory
     */
    public function __construct(
        \Vnecoms\VendorsCategory\Model\Rewrite\ChildrenCategoriesProvider $childrenCategoriesProvider,
        CategoryUrlRewriteGenerator $categoryUrlRewriteGenerator,
        UrlPersistInterface $urlPersist,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    ) {
        $this->childrenCategoriesProvider = $childrenCategoriesProvider;
        $this->categoryUrlRewriteGenerator = $categoryUrlRewriteGenerator;
        $this->urlPersist = $urlPersist;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @param Category $category
     */
    public function deleteCategoryRewritesForChildren(Category $category)
    {
        $categoryIds = $this->childrenCategoriesProvider->getChildrenIds($category, true);
        $categoryIds[] = $category->getId();
        foreach ($categoryIds as $categoryId) {
            $this->urlPersist->deleteByData(
                [
                    UrlRewrite::ENTITY_ID => $categoryId,
                    UrlRewrite::ENTITY_TYPE => 'vendor_category',
                ]
            );
//            $this->urlPersist->deleteByData(
//                [
//                    UrlRewrite::METADATA => serialize(['category_id' => $categoryId]),
//                    UrlRewrite::ENTITY_TYPE => ProductUrlRewriteGenerator::ENTITY_TYPE,
//                ]
//            );
        }
    }
}
