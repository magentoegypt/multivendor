<?php
/**
 * Top-level categories for the header search selector.
 *
 * Figma attaches an "All Categories" dropdown to the left of the search field.
 * This makes it a REAL filter rather than decoration: the option value is the
 * category id and the select posts as `cat`, which Magento's catalog search layer
 * (Magento\CatalogSearch\Model\Layer\Filter\Category) reads to scope the results.
 * So choosing "Grocery" and searching genuinely narrows the result set.
 *
 * Only categories that are active, included in the menu AND have products are
 * offered — sending a shopper into an empty result set is worse than offering one
 * fewer option, the same rule the homepage category chips already follow.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\ViewModel;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class SearchCategories implements ArgumentInterface
{
    private CollectionFactory $collectionFactory;
    private StoreManagerInterface $storeManager;
    private LoggerInterface $logger;

    /** @var array<int, array{id:int,name:string}>|null */
    private ?array $cache = null;

    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * @return array<int, array{id:int,name:string}>
     */
    public function getCategories(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        try {
            $store = $this->storeManager->getStore();
            $rootId = (int) $store->getRootCategoryId();

            $collection = $this->collectionFactory->create();
            $collection->addAttributeToSelect(['name', 'is_active', 'include_in_menu'])
                ->addFieldToFilter('parent_id', $rootId)
                ->addFieldToFilter('is_active', 1)
                ->addFieldToFilter('include_in_menu', 1)
                ->setStore($store)
                ->addAttributeToSort('position', 'ASC');

            $categories = [];
            foreach ($collection as $category) {
                $categories[(int) $category->getId()] = $category;
            }

            $counts = $this->visibleProductCounts($collection, array_keys($categories), (int) $store->getId());

            $out = [];
            foreach ($categories as $id => $category) {
                // Never offer a filter that leads nowhere.
                if (($counts[$id] ?? 0) < 1) {
                    continue;
                }
                $out[] = [
                    'id'   => $id,
                    'name' => (string) $category->getName(),
                ];
            }

            return $this->cache = $out;
        } catch (\Throwable $e) {
            // A broken selector must never take the header — and therefore every
            // page — down. Degrade to no selector at all.
            $this->logger->warning('Hub Market search categories: ' . $e->getMessage());
            return $this->cache = [];
        }
    }

    /**
     * How many products each of these categories actually SHOWS, per the
     * category index for this store.
     *
     * NOT `$category->getProductCount()`, which counts the rows in
     * catalog_category_product — the products assigned to that category
     * DIRECTLY. A parent whose products all live in its subcategories counts
     * zero there and was dropped from the selector, even though its page lists
     * them: "Fresh Food" was created with its seven products under
     * "Dairy & Eggs", and it never appeared ([CL036-TC13]). The admin tree
     * disagreed with the storefront for the same reason — it rolls the subtree
     * up, and the selector did not.
     *
     * The index is the right source twice over: it rolls anchors up the way the
     * category page does, and it already excludes what the storefront will not
     * show, so a category whose only products are disabled still counts zero.
     *
     * Read through the collection's own connection rather than through an
     * injected ResourceConnection: adding a constructor argument to a class
     * this old means `setup:di:compile`, and that wipes `generated/` — several
     * minutes of 500s on a live storefront, for a dropdown.
     *
     * FAILS OPEN. If the per-store index table is not there (a partial install,
     * a mid-reindex switch), every category is let through rather than none:
     * a selector with an extra entry beats a header with no selector.
     *
     * @param int[] $ids
     * @return array<int, int>
     */
    private function visibleProductCounts($collection, array $ids, int $storeId): array
    {
        if (!$ids) {
            return [];
        }

        try {
            $connection = $collection->getConnection();
            $table = $collection->getResource()->getTable('catalog_category_product_index_store' . $storeId);

            if (!$connection->isTableExists($table)) {
                return array_fill_keys($ids, 1);
            }

            $select = $connection->select()
                ->from($table, ['category_id', 'products' => new \Zend_Db_Expr('COUNT(*)')])
                ->where('category_id IN (?)', $ids)
                ->group('category_id');

            return array_map('intval', $connection->fetchPairs($select));
        } catch (\Throwable $e) {
            $this->logger->warning('Hub Market search categories, product counts: ' . $e->getMessage());

            return array_fill_keys($ids, 1);
        }
    }
}
