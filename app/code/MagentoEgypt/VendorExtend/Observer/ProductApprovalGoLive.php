<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Observer;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Vnecoms\VendorsProduct\Model\Source\Approval;

/**
 * Hub Market: a vendor product goes online the moment an admin approves it, and offline the
 * moment it is rejected.
 *
 * Vnecoms' Approve / Mass Approve / Reject / Mass Unapprove only write the `approval`
 * attribute (Resource::saveAttribute). Nothing else happens at that point:
 *   - the search indexes (OpenSearch fallback, and Algolia, which serves search here) admit
 *     approved products only, and waited for the "Update by Schedule" cron, then for Algolia's
 *     own queue on top;
 *   - category/search pages already in Varnish kept being served without the product;
 *   - a product whose legacy stock index row was stuck at "out of stock" stayed hidden however
 *     long anyone waited: MSI's default-stock indexer re-reads that row instead of recomputing it
 *     (ClickUp TC32 86d4bcryz). Only an MSI source-item save refreshes it.
 * All four controllers dispatch `vnecoms_vendors_push_notification` (type product_approval) once
 * per product AFTER saving the attribute, so this runs then and does, for that product:
 *   1. re-saves its default source item through MSI (same values), which refreshes that row;
 *   2. reindexes stock, price, category and search rows for it (and a configurable's children);
 *   3. pushes it straight to Algolia for every store, bypassing the once-a-minute queue;
 *   4. cleans its cache tags, which purges the pages that list it from Varnish.
 *
 * Failures are logged and swallowed: approval itself must never fail because of this.
 * No constructor dependencies on purpose: this class was added without a di:compile, and
 * production's compiled DI builds unknown classes with no arguments.
 */
class ProductApprovalGoLive implements ObserverInterface
{
    private const INDEXERS = [
        'cataloginventory_stock',
        'catalog_product_price',
        'catalog_product_category',
        'catalogsearch_fulltext',
    ];

    public function execute(Observer $observer)
    {
        $event = $observer->getEvent();
        if ($event->getData('type') !== 'product_approval') {
            return;
        }
        $info = (array) $event->getData('additional_info');
        $productId = (int) ($info['id'] ?? 0);
        if (!$productId) {
            return;
        }

        $om = ObjectManager::getInstance();
        $logger = $om->get(\Psr\Log\LoggerInterface::class);

        try {
            /** @var \Magento\Catalog\Model\Product $product */
            $product = $om->create(\Magento\Catalog\Model\Product::class)->setStoreId(0)->load($productId);
            if (!$product->getId()) {
                return;
            }
            $ids = [$productId];
            if ($product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
                $ids = array_merge($ids, array_map('intval', $product->getTypeInstance()->getChildrenIds($productId)[0] ?? []));
            }
            $approved = (int) $product->getData('approval') === Approval::STATUS_APPROVED;

            if ($approved) {
                $this->refreshStockStatus($om, $ids);
            }
            foreach (self::INDEXERS as $indexerId) {
                $om->create(\Magento\Indexer\Model\Indexer::class)->load($indexerId)->reindexList($ids);
            }
            $this->pushToAlgolia($om, $ids);
            $this->cleanPageCache($om, $ids, $product->getCategoryIds());
        } catch (\Throwable $e) {
            $logger->error('Hub Market approval go-live failed for product ' . $productId . ': ' . $e->getMessage());
        }
    }

    /**
     * Re-save the default source items (unchanged) so MSI rewrites the legacy stock status row.
     */
    private function refreshStockStatus(ObjectManager $om, array $ids): void
    {
        $skus = array_values($om->get(\Magento\InventoryCatalogApi\Model\GetSkusByProductIdsInterface::class)->execute($ids));
        if (!$skus) {
            return;
        }
        $criteria = $om->create(\Magento\Framework\Api\SearchCriteriaBuilder::class)
            ->addFilter('sku', $skus, 'in')
            ->addFilter('source_code', 'default')
            ->create();
        $items = $om->get(\Magento\InventoryApi\Api\SourceItemRepositoryInterface::class)->getList($criteria)->getItems();
        if ($items) {
            $om->get(\Magento\InventoryApi\Api\SourceItemsSaveInterface::class)->execute(array_values($items));
        }
    }

    /**
     * Clear the product's pages AND the category pages that list it, in Magento's cache and
     * Varnish. Same route indexers take: CacheContext + clean_cache_by_tags.
     */
    private function cleanPageCache(ObjectManager $om, array $ids, array $categoryIds): void
    {
        /** @var \Magento\Framework\Indexer\CacheContext $context */
        $context = $om->create(\Magento\Framework\Indexer\CacheContext::class);
        $context->registerEntities(\Magento\Catalog\Model\Product::CACHE_TAG, $ids);
        if ($categoryIds) {
            $context->registerEntities(\Magento\Catalog\Model\Category::CACHE_TAG, array_map('intval', $categoryIds));
            $context->registerEntities(
                \Magento\Catalog\Model\Product::CACHE_PRODUCT_CATEGORY_TAG,
                array_map('intval', $categoryIds)
            );
        }
        $om->get(\Magento\Framework\Event\ManagerInterface::class)
            ->dispatch('clean_cache_by_tags', ['object' => $context]);
        $om->get(\Magento\Framework\App\CacheInterface::class)->clean($context->getIdentities());
    }

    /**
     * Index the products into Algolia now for every store, instead of via the queue.
     * Algolia's builder skips stores where Algolia indexing is off (the Luma stores).
     */
    private function pushToAlgolia(ObjectManager $om, array $ids): void
    {
        if (!class_exists(\Algolia\AlgoliaSearch\Helper\Data::class)) {
            return;
        }
        $helper = $om->get(\Algolia\AlgoliaSearch\Helper\Data::class);
        foreach ($om->get(\Magento\Store\Model\StoreManagerInterface::class)->getStores() as $store) {
            if ($store->getIsActive()) {
                $helper->rebuildStoreProductIndex((int) $store->getId(), $ids);
            }
        }
    }
}
