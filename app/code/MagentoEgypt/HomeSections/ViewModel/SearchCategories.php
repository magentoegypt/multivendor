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

            $out = [];
            foreach ($collection as $category) {
                // Never offer a filter that leads nowhere.
                if ((int) $category->getProductCount() < 1) {
                    continue;
                }
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
