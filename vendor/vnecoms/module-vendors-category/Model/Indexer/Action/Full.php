<?php
/**
 * *
 *  * Full.php
 *  *
 *  * @file        Full.php
 *  * @copyright   Copyright (c) 2017 Vnecoms (https://www.vnecoms.com)
 *  * @site        https://www.vnecoms.com/
 *  * @author      Vnecoms <support@vnecoms.com>
 *  * @author      HiepNT <hiepnt@vnecoms.com>
 *
 */

/**
 * *
 *  * Full.php
 *  *
 *  * @file        Full.php
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
use Vnecoms\VendorsCategory\Model\Rewrite\CategoryUrlRewriteGenerator;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Model\UrlFinderInterface;

class Full
{
    /** @var CategoryUrlRewriteGenerator */
    protected $categoryUrlRewriteGenerator;

    /** @var UrlPersistInterface */
    protected $urlPersist;

    /**
     * @var \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var \Vnecoms\VendorsCategory\Model\Rewrite\CategoryUrlPathGenerator
     */
    protected $categoryUrlPathGenerator;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    protected $_vendors;

    /** @var UrlFinderInterface */
    protected $urlFinder;

    public function __construct(
        \Vnecoms\VendorsCategory\Model\ResourceModel\Category\CollectionFactory $collectionFactory,
        CategoryUrlRewriteGenerator $categoryUrlRewriteGenerator,
        \Vnecoms\VendorsCategory\Model\Rewrite\CategoryUrlPathGenerator $categoryUrlPathGenerator,
        UrlPersistInterface $urlPersist,
        \Magento\Framework\App\ResourceConnection $connection,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        UrlFinderInterface $urlFinder
    ) {
        $this->categoryCollectionFactory = $collectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->categoryUrlRewriteGenerator = $categoryUrlRewriteGenerator;
        $this->urlPersist = $urlPersist;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->connection = $connection;
        $this->storeManager = $storeManager;
        $this->urlFinder = $urlFinder;
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
     * @return $this
     */
    public function reindexAll()
    {
        // echo 'test';
        $stores = $this->storeManager->getStores();
        $collection = $this->categoryCollectionFactory->create()->addFieldToFilter('level', ['gt' => 0]);
        $tableName = $this->categoryCollectionFactory->create()->getTable('ves_vendorscategory_category');

        if (!$this->connection->getConnection()->isTableExists($tableName)) {
            return;
        }
        foreach ($collection as $category) {
            try {
                $category = $this->categoryRepository->get($category->getId());
            } catch (NoSuchEntityException $e) {
                continue;
            }
            $this->saveTableCategory($category);

            $urlRewrites = $this->categoryUrlRewriteGenerator->generate($category, true);
            $this->urlPersist->replace($urlRewrites);
            //$urlRewrites = $this->categoryUrlRewriteGenerator->generate($category);

           // var_dump($urlRewrites);die();
//            $vendorId 	= $category->getVendorId();
//            if(!isset($this->_vendors[$vendorId])){
//                $this->_vendors[$vendorId] = \Magento\Framework\App\ObjectManager::getInstance()
//                    ->get('Vnecoms\Vendors\Model\Vendor')->load($vendorId);
//            }
//            $vendor 	= $this->_vendors[$vendorId];
//            $vendorId 	= $vendor->getVendorId();
//            $storeIds = $this->storeManager->getWebsite($vendor->getWebsiteId())->getStoreIds();
//
//            foreach($storeIds as $storeId) {
//                $store = $this->storeManager->getStore($storeId);
//
//                $targetPath = $this->categoryUrlPathGenerator->getCanonicalUrlPath($category);
//                $requestPath = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($category, $store->getId(), true);
//
//           //     echo $targetPath.'<br/>';
//            //    echo $requestPath.'<br/>';
//
//                $rewrite = $this->urlFinder->findOneByData([
//                    UrlRewrite::ENTITY_ID => $category->getId(),
//                    UrlRewrite::STORE_ID => $store->getId(),
//                    UrlRewrite::ENTITY_TYPE => 'vendor_category',
//                    UrlRewrite::REDIRECT_TYPE => 0
//                ]);
//
//                if($rewrite) {
//                    $bind = ['request_path' => $requestPath, 'target_path' => $targetPath];
//                    $where = [
//                        'entity_id = ?' => $category->getId(),
//                        'entity_type like ?' => '%vendor_category%',
//                        'store_id = ?' => $store->getId(),
//                        'redirect_type = ?' => 0
//                    ];
//
//                    $this->connection->getConnection()->update(
//                        'url_rewrite',
//                        $bind,
//                        $where
//                    );
//                } else {
//                    $this->connection->getConnection()->insert(
//                        'url_rewrite',
//                        [
//                            'entity_id' => $category->getId(),
//                            'entity_type' => 'vendor_category',
//                            'store_id' => $store->getId(),
//                            'redirect_type' => 0,
//                            'request_path' => $requestPath,
//                            'target_path' => $targetPath
//                        ]
//                    );
//                }
//
//                $currentUrlRewrites = $this->urlFinder->findAllByData(
//                    [
//                        UrlRewrite::STORE_ID => $storeId,
//                        UrlRewrite::ENTITY_ID => $category->getId(),
//                        UrlRewrite::ENTITY_TYPE => 'vendor_category',
//                    ]
//                );
//
//                $targetPath = $this->categoryUrlPathGenerator->getUrlPathWithSuffix($category, $storeId);
//
//                if($rewrite) {
//                    $bind = ['request_path' => $requestPath, 'target_path' => $targetPath];
//                    $where = [
//                        'entity_id = ?' => $category->getId(),
//                        'entity_type like ?' => '%vendor_category%',
//                        'store_id = ?' => $store->getId(),
//                        'redirect_type = ?' => 301
//                    ];
//
//                    $this->connection->getConnection()->update(
//                        'url_rewrite',
//                        $bind,
//                        $where
//                    );
//                }
//            }
        }

        return $this;
    }
}
