<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Observer;

use Vnecoms\VendorsCategory\Model\Category;
use Vnecoms\VendorsCategory\Model\Rewrite\CategoryUrlRewriteGenerator;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\Framework\Event\ObserverInterface;

class CategoryProcessUrlRewriteSavingObserver implements ObserverInterface
{
    /** @var CategoryUrlRewriteGenerator */
    protected $categoryUrlRewriteGenerator;

    /** @var UrlPersistInterface */
    protected $urlPersist;

    protected $vendorSession;

    /**
     * @param CategoryUrlRewriteGenerator $categoryUrlRewriteGenerator
     * @param UrlPersistInterface         $urlPersist
     */
    public function __construct(
        CategoryUrlRewriteGenerator $categoryUrlRewriteGenerator,
        UrlPersistInterface $urlPersist
    ) {
        $this->categoryUrlRewriteGenerator = $categoryUrlRewriteGenerator;
        $this->urlPersist = $urlPersist;
    }

    /**
     * Generate urls for UrlRewrite and save it in storage.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Category $category */
        $category = $observer->getEvent()->getCategory();
        if ($category->getId() == $category->getRootId($category->getVendorId())) {
            return;
        }
        if ($category->dataHasChangedFor('url_key')) {
            $urlRewrites = $this->categoryUrlRewriteGenerator->generate($category);
            $this->urlPersist->replace($urlRewrites);
        }
    }
}
