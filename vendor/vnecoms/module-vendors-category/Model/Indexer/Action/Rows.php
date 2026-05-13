<?php
/**
 * *
 *  * Rows.php
 *  *
 *  * @file        Rows.php
 *  * @copyright   Copyright (c) 2017 Vnecoms (https://www.vnecoms.com)
 *  * @site        https://www.vnecoms.com/
 *  * @author      Vnecoms <support@vnecoms.com>
 *  * @author      HiepNT <hiepnt@vnecoms.com>
 *
 */

/**
 * *
 *  * Rows.php
 *  *
 *  * @file        Rows.php
 *  * @copyright   Copyright (c) 2017 Vnecoms (https://www.vnecoms.com)
 *  * @site        https://www.vnecoms.com/
 *  * @author      Vnecoms <support@vnecoms.com>
 *  * @author      HiepNT <hiepnt@vnecoms.com>
 *
 */

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Vnecoms\VendorsCategory\Model\Indexer\Action;

use Vnecoms\VendorsCategory\Api\CategoryRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Vnecoms\VendorsCategory\Model\Rewrite\CategoryUrlRewriteGenerator;
use Magento\UrlRewrite\Model\UrlPersistInterface;

class Rows
{
    /**
     * XML path for category url suffix.
     */
    const XML_PATH_CATEGORY_URL_SUFFIX = 'catalog/seo/category_url_suffix';

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface */
    protected $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $connection;

    /**
     * @var array
     */
    protected $_vendors;

    protected $categoryCollectionFactory;

    protected $categoryUrlPathGenerator;

    /** @var UrlFinderInterface */
    protected $urlFinder;

    /** @var CategoryUrlRewriteGenerator */
    protected $categoryUrlRewriteGenerator;

    /** @var UrlPersistInterface */
    protected $urlPersist;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\ResourceConnection $connection,
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory $collectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Vnecoms\VendorsCategory\Model\Rewrite\CategoryUrlPathGenerator $categoryUrlPathGenerator,
        UrlFinderInterface $urlFinder,
        CategoryUrlRewriteGenerator $categoryUrlRewriteGenerator,
        UrlPersistInterface $urlPersist
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->connection = $connection;
        $this->scopeConfig = $scopeConfig;
        $this->categoryCollectionFactory = $collectionFactory;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->urlFinder = $urlFinder;
        $this->categoryUrlRewriteGenerator = $categoryUrlRewriteGenerator;
        $this->urlPersist = $urlPersist;

        $this->_vendors = [];
    }

    public function saveTableCategory($category)
    {
        if (!$category->getId()) {
            return;
        }
        $this->connection->getConnection()
            ->update(
                $this->categoryCollectionFactory->create()->getTable('ves_vendorscategory_category'),
                ['url_path' => $this->categoryUrlPathGenerator->getUrlPath($category, $category->getStoreId(), true)],
                ['category_id = ?' => $category->getId()]
            );
    }

    /**
     * Refresh entities index.
     *
     * @param int[] $entityIds
     * @param bool  $useTempTable
     *
     * @return Rows
     */
    public function reindex(array $entityIds = [], $useTempTable = false)
    {

        /* @var $store \Magento\Store\Model\Store */
       // foreach ($stores as $store) {
            $tableName = $this->categoryCollectionFactory->create()->getTable('ves_vendorscategory_category');

        if (!$this->connection->isTableExists($tableName)) {
            return;
        }

        foreach ($entityIds as $entityId) {
            try {
                $category = $this->categoryRepository->get($entityId);
            } catch (NoSuchEntityException $e) {
                continue;
            }

            $this->saveTableCategory($category);

            $vendorId = $category->getVendorId();
            if (!isset($this->_vendors[$vendorId])) {
                $this->_vendors[$vendorId] = \Magento\Framework\App\ObjectManager::getInstance()
                        ->get('Vnecoms\Vendors\Model\Vendor')->load($vendorId);
            }
            $vendor = $this->_vendors[$vendorId];
            $vendorId = $vendor->getVendorId();
            $storeIds = $this->storeManager->getWebsite($vendor->getWebsiteId())->getStoreIds();

            $targetPath = $this->categoryUrlPathGenerator->getCanonicalUrlPath($category);
            $requestPath = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($category, $store->getId(), true);

                //shopy/hiep/test.html
                //shopping/hiep/test.html

            foreach ($storeIds as $storeId) {
                $store = $this->storeManager->getStore($storeId);

                $targetPath = $this->categoryUrlPathGenerator->getCanonicalUrlPath($category);
                $requestPath = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($category, $store->getId());

             //   echo $targetPath;
              //  echo $requestPath;
                $rewrite = $this->urlFinder->findOneByData([
                UrlRewrite::ENTITY_ID => $category->getId(),
                UrlRewrite::STORE_ID => $store->getId(),
                UrlRewrite::ENTITY_TYPE => 'vendor_category',
                UrlRewrite::REDIRECT_TYPE => 0,
                ]);

                if ($rewrite) {
                    $bind = ['request_path' => $requestPath];
                    $where = [
                        'entity_id = ?' => $category->getId(),
                        'entity_type like ?' => '%vendor_category%',
                        'store_id = ?' => $store->getId(),
                        'redirect_type = ?' => 0,
                    ];

                    $this->connection->getConnection()->update(
                        'url_rewrite',
                        $bind,
                        $where
                    );
                } else {
                    $this->connection->getConnection()->insert(
                        'url_rewrite',
                        [
                            'entity_id' => $category->getId(),
                            'entity_type' => 'vendor_category',
                            'store_id' => $store->getId(),
                            'redirect_type' => 0,
                            'request_path' => $requestPath,
                            'target_path' => $targetPath,
                        ]
                    );
                }
            }
        }
      //  }
    }
}
