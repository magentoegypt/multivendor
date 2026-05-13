<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model\Rewrite;

use Vnecoms\VendorsCategory\Model\Category;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;

class CanonicalUrlRewriteGenerator
{
    /** @var \Vnecoms\VendorsCategory\Model\Rewrite\CategoryUrlPathGenerator */
    protected $categoryUrlPathGenerator;

    /** @var \Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory */
    protected $urlRewriteFactory;

    /**
     * @param \Vnecoms\VendorsCategory\Model\Rewrite\CategoryUrlPathGenerator $categoryUrlPathGenerator
     * @param \Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory           $urlRewriteFactory
     */
    public function __construct(
        CategoryUrlPathGenerator $categoryUrlPathGenerator,
        UrlRewriteFactory $urlRewriteFactory
    ) {
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->urlRewriteFactory = $urlRewriteFactory;
    }

    /**
     * Generate list based on store view.
     *
     * @param int                                     $storeId
     * @param \Vnecoms\VendorsCategory\Model\Category $category
     *
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite[]
     */
    public function generate($storeId, Category $category, $isIndexMode)
    {
        $urlPath = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($category, $storeId, $isIndexMode);
        $result = [
            $urlPath.'_'.$storeId => $this->urlRewriteFactory->create()->setStoreId($storeId)
                ->setEntityType('vendor_category')
                ->setEntityId($category->getId())
                ->setRequestPath($urlPath)
                ->setTargetPath($this->categoryUrlPathGenerator->getCanonicalUrlPath($category)),
        ];
       // var_dump($result);die();

        return $result;
    }
}
