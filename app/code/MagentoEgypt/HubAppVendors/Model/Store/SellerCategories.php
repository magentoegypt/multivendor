<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\GraphQl\Query\Uid;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\Seller\ListableProducts;
use MagentoEgypt\HubApp\Model\Seller\SellerDirectory;
use Psr\Log\LoggerInterface;

/**
 * Sellers by top-level category: the Stores chips' counts (hmStoreCategories)
 * and each card's primary category (HmStoreCard.primary_category).
 *
 * The categories are the store view's top-level menu categories (level 2 under
 * its root category, active, in the menu), in menu order — the set the app's
 * Stores chips and Home's "Shop by category" show. A seller is in a category
 * when one of its listable products (ListableProducts, the storefront's gate)
 * is filed under the category or any of its children: the hmStores
 * category_id rule (CategorySellers), so a chip's count is the number of
 * sellers its list shows. A seller's primary category is the one holding most
 * of its listable products (menu order breaks a tie).
 *
 * Built for every approved seller of a store view at once — one category
 * collection and one assignment query over the listable products — and kept in
 * the `hubapp` app cache (AppCache::MAX_TTL, tagged hm_vendor and cat_c: a
 * seller or a category save rebuilds it; a product moved between categories
 * shows within the TTL, like the product counts).
 */
class SellerCategories
{
    private const CACHE_KEY_PREFIX = 'seller_categories_';

    /** @var array<int, array{categories: array<int, array{id: int, name: string}>, counts: array<int, array<int, int>>}> */
    private array $byStore = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly CollectionFactory $categoryCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly ListableProducts $listableProducts,
        private readonly SellerDirectory $directory,
        private readonly AppCache $appCache,
        private readonly Uid $uid,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * The seller's primary category with how many of its listable products are in it; null when none.
     *
     * @return array{id: int, uid: string, name: string, count: int}|null
     */
    public function primary(int $vendorId, int $storeId): ?array
    {
        $data = $this->load($storeId);
        $counts = $data['counts'][$vendorId] ?? [];
        $best = null;
        foreach ($data['categories'] as $category) {
            $count = (int) ($counts[$category['id']] ?? 0);
            if ($count > 0 && ($best === null || $count > $best['count'])) {
                $best = $this->chip($category, $count);
            }
        }

        return $best;
    }

    /**
     * The Stores chips: sellers with a listable product (total_count) and, per category in menu
     * order, how many of them it holds; categories with none are left out.
     *
     * @return array{total_count: int, items: array<int, array{id: int, uid: string, name: string, count: int}>}
     */
    public function chips(int $storeId): array
    {
        $data = $this->load($storeId);
        $items = [];
        foreach ($data['categories'] as $category) {
            $sellers = 0;
            foreach ($data['counts'] as $counts) {
                if ((int) ($counts[$category['id']] ?? 0) > 0) {
                    $sellers++;
                }
            }
            if ($sellers > 0) {
                $items[] = $this->chip($category, $sellers);
            }
        }

        return ['total_count' => count($data['counts']), 'items' => $items];
    }

    /**
     * @param array{id: int, name: string} $category
     * @return array{id: int, uid: string, name: string, count: int}
     */
    private function chip(array $category, int $count): array
    {
        return [
            'id' => $category['id'],
            'uid' => $this->uid->encode((string) $category['id']),
            'name' => $category['name'],
            'count' => $count,
        ];
    }

    /**
     * @return array{categories: array<int, array{id: int, name: string}>, counts: array<int, array<int, int>>}
     */
    private function load(int $storeId): array
    {
        if (isset($this->byStore[$storeId])) {
            return $this->byStore[$storeId];
        }

        $key = self::CACHE_KEY_PREFIX . $storeId;
        $cached = $this->appCache->load($key);
        if ($cached !== null) {
            return $this->byStore[$storeId] = self::fromCache($cached);
        }

        $built = $this->build($storeId);
        if ($built !== null) {
            $this->appCache->save($key, $built, [Tags::VENDOR, Tags::CATEGORY], AppCache::MAX_TTL);
        }

        return $this->byStore[$storeId] = $built ?? ['categories' => [], 'counts' => []];
    }

    /**
     * @return array{categories: array<int, array{id: int, name: string}>, counts: array<int, array<int, int>>}|null
     *         null when it could not be read (not cached then)
     */
    private function build(int $storeId): ?array
    {
        $categories = $this->topCategories($storeId);
        if ($categories === null) {
            return null;
        }

        //  Approved sellers with at least one listable product: the hmStores candidates.
        $listable = array_filter(
            $this->listableProducts->forVendors($this->directory->approvedIds(), $storeId),
            static fn (array $productIds): bool => $productIds !== []
        );
        $owner = [];
        $counts = [];
        foreach ($listable as $vendorId => $productIds) {
            $counts[(int) $vendorId] = [];
            foreach ($productIds as $productId) {
                $owner[(int) $productId] = (int) $vendorId;
            }
        }
        if (!$categories || !$owner) {
            return ['categories' => $categories, 'counts' => $counts];
        }

        $top = [];
        foreach ($categories as $category) {
            $top[$category['id']] = true;
        }

        try {
            $connection = $this->resource->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from(['ccp' => $this->resource->getTableName('catalog_category_product')], ['product_id'])
                    ->join(
                        ['cce' => $this->resource->getTableName('catalog_category_entity')],
                        'cce.entity_id = ccp.category_id',
                        ['path']
                    )
                    ->where('ccp.product_id IN (?)', array_keys($owner))
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: seller categories unavailable: ' . $e->getMessage());

            return null;
        }

        //  A product counts once per top-level category, however many of its children hold it.
        $seen = [];
        foreach ($rows as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $segments = explode('/', (string) ($row['path'] ?? ''));
            //  "1/<root>/<top>/…": the top-level category is the third id of the path.
            $topId = isset($segments[2]) ? (int) $segments[2] : 0;
            if (!isset($owner[$productId], $top[$topId]) || isset($seen[$productId][$topId])) {
                continue;
            }
            $seen[$productId][$topId] = true;
            $vendorId = $owner[$productId];
            $counts[$vendorId][$topId] = ($counts[$vendorId][$topId] ?? 0) + 1;
        }

        return ['categories' => $categories, 'counts' => $counts];
    }

    /**
     * Top-level menu categories of the store view's tree, in menu order; null when unreadable.
     *
     * @return array<int, array{id: int, name: string}>|null
     */
    private function topCategories(int $storeId): ?array
    {
        try {
            $rootId = (int) $this->storeManager->getStore($storeId)->getRootCategoryId();
            if ($rootId < 1) {
                return [];
            }
            $collection = $this->categoryCollectionFactory->create();
            $collection->setStoreId($storeId)
                ->addAttributeToSelect(['name'])
                ->addAttributeToFilter('is_active', 1)
                ->addAttributeToFilter('include_in_menu', 1)
                ->addFieldToFilter('level', 2)
                ->addFieldToFilter('path', ['like' => '1/' . $rootId . '/%'])
                ->addAttributeToSort('position', 'ASC');

            $out = [];
            foreach ($collection as $category) {
                $name = trim((string) $category->getName());
                if ((int) $category->getId() > 0 && $name !== '') {
                    $out[] = ['id' => (int) $category->getId(), 'name' => $name];
                }
            }

            return $out;
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: top-level categories unavailable: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param array<mixed> $cached
     * @return array{categories: array<int, array{id: int, name: string}>, counts: array<int, array<int, int>>}
     */
    private static function fromCache(array $cached): array
    {
        $categories = [];
        foreach ((array) ($cached['categories'] ?? []) as $category) {
            if (is_array($category) && (int) ($category['id'] ?? 0) > 0) {
                $categories[] = ['id' => (int) $category['id'], 'name' => (string) ($category['name'] ?? '')];
            }
        }
        $counts = [];
        foreach ((array) ($cached['counts'] ?? []) as $vendorId => $byCategory) {
            $counts[(int) $vendorId] = [];
            foreach ((array) $byCategory as $categoryId => $count) {
                $counts[(int) $vendorId][(int) $categoryId] = (int) $count;
            }
        }

        return ['categories' => $categories, 'counts' => $counts];
    }
}
