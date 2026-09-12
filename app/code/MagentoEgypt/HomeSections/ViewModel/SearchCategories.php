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
 * The gate is ENABLED, and nothing else. A category the merchant has switched on
 * belongs in this list whether or not it is in the menu: the menu is a curated
 * navigation band with room for ten items, while this is a search scope, and the
 * two answer different questions. Requested 2026-09-12 — "not depends on include
 * in menu but enabled need to show there" — which took the list from 10 to 27.
 *
 * Top level only, deliberately: the admin tree runs four levels and 135 enabled
 * categories, which is a scroll, not a selector.
 *
 * Nor is there a has-products test any more. There used to be one, on the
 * principle that a filter should never lead to an empty page; it is gone because
 * "show every enabled category" cannot be honoured while also dropping some of
 * them. If it is ever wanted back, count through
 * `catalog_category_product_index_store<N>` and NOT `getProductCount()` — the
 * latter counts only DIRECTLY assigned rows, so a parent whose products all live
 * in its children counts zero ("Fresh Food" disappeared exactly that way, see
 * CL036-TC13), while the index rolls anchors up the way the category page does.
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
            $collection->addAttributeToSelect(['name', 'is_active'])
                ->addFieldToFilter('parent_id', $rootId)
                ->addFieldToFilter('is_active', 1)
                ->setStore($store)
                ->addAttributeToSort('position', 'ASC');

            $out = [];
            foreach ($collection as $category) {
                $out[] = [
                    'id'   => (int) $category->getId(),
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
}
