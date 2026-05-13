<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model\Rewrite;

use Vnecoms\VendorsCategory\Model\Category;
use Magento\Store\Model\Store;
use Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface;

class CategoryUrlRewriteGenerator
{
    /** @var \Vnecoms\VendorsCategory\Model\Category */
    protected $category;

    /** @var \Vnecoms\VendorsCategory\Model\Rewrite\CanonicalUrlRewriteGenerator */
    protected $canonicalUrlRewriteGenerator;

    /** @var \Vnecoms\VendorsCategory\Model\Rewrite\CurrentUrlRewritesRegenerator */
    protected $currentUrlRewritesRegenerator;

    /** @var \Vnecoms\VendorsCategory\Model\Rewrite\ChildrenUrlRewriteGenerator */
    protected $childrenUrlRewriteGenerator;

    /**
     * @var bool
     */
    protected $overrideStoreUrls;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        CanonicalUrlRewriteGenerator $canonicalUrlRewriteGenerator,
        CurrentUrlRewritesRegenerator $currentUrlRewritesRegenerator,
        ChildrenUrlRewriteGenerator $childrenUrlRewriteGenerator,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->canonicalUrlRewriteGenerator = $canonicalUrlRewriteGenerator;
        $this->childrenUrlRewriteGenerator = $childrenUrlRewriteGenerator;
        $this->currentUrlRewritesRegenerator = $currentUrlRewritesRegenerator;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($category, $isIndexMode = false)
    {
        $this->category = $category;
        $vendor = \Magento\Framework\App\ObjectManager::getInstance()
            ->get('Vnecoms\Vendors\Model\Vendor')->load($this->category->getVendorId());

        $urls = [];

        foreach ($this->storeManager->getWebsite($vendor->getWebsiteId())->getStoreIds() as $storeId) {
            $store = $this->storeManager->getStore($storeId);
            $urls = array_merge($urls, $this->generateForSpecificStoreView($storeId, $isIndexMode));
        }
        $this->category = null;

        // var_dump($urls);die();
      //  $storeId = $this->category->getStoreId();
        return $urls;
    }

    /**
     * Generate list of urls per store.
     *
     * @param string $storeId
     *
     * @return \Magento\UrlRewrite\Service\V1\Data\UrlRewrite[]
     */
    protected function generateForSpecificStoreView($storeId, $isIndexMode)
    {
        $urls = array_merge(
            $this->canonicalUrlRewriteGenerator->generate($storeId, $this->category, $isIndexMode),
            $this->childrenUrlRewriteGenerator->generate($storeId, $this->category, $isIndexMode),
            $this->currentUrlRewritesRegenerator->generate($storeId, $this->category, $isIndexMode)
        );

        return $urls;
    }
}
