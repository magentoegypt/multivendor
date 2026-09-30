<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Model\BundleDeal;

use Magento\Catalog\Helper\Image as ImageHelper;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\VendorExtend\Model\StorefrontVisibility;
use Psr\Log\LoggerInterface;

/**
 * Bundle cards as DATA — the rules of Block\BundleDeals (the website's bundle
 * rail and bundles page), extracted so the app (hmBundleDeals, BUNDLE_DEALS)
 * and, later, the website build them once.
 *
 * Same rules as the block, see its header for the reasoning:
 *   - `bundle` and `new_bundle` (this project's type, core bundle tables);
 *   - a bundle without options or without an indexed price is skipped;
 *   - REQUIRED options count items: more than one = a kit ("4 items"), one =
 *     a chooser ("Choose from 7");
 *   - the saving compares the same cheapest selections at REGULAR prices and is
 *     only reported when it is real (> 0.005);
 *   - a chooser compares "from" prices, which can only understate a saving;
 *   - description: plain excerpt of the bundle's own copy, else its contents;
 *   - top-level ACTIVE categories for the filter chips;
 *   - rating from the default-scope review summary.
 *
 * One addition, as the design asks: the storefront visibility gate
 * (StorefrontVisibility sellable + searchable) — the block lists any enabled,
 * visible bundle, including one of an inactive seller.
 *
 * Prices are raw floats in the store's base currency (index values); callers
 * format or convert them. Images come from the catalog image helper, so the
 * caller must be in the storefront (frontend area or emulation).
 */
class BundleDealBuilder
{
    public const TYPES = ['bundle', 'new_bundle'];

    public const THUMB_LIMIT = 4;

    public function __construct(
        private readonly CollectionFactory $collectionFactory,
        private readonly ResourceConnection $resource,
        private readonly ImageHelper $imageHelper,
        private readonly StoreManagerInterface $storeManager,
        private readonly Visibility $visibility,
        private readonly StorefrontVisibility $storefrontVisibility,
        private readonly EavConfig $eavConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Every sellable bundle card of the store view, newest first.
     *
     * @return array{
     *     items: array<int, array<string, mixed>>,
     *     category_names: array<int, string>
     * } category_names: id => name of the active top-level categories, catalogue order
     */
    public function build(int $storeId, int $max = 200): array
    {
        $store = $this->storeManager->getStore($storeId);

        $collection = $this->collectionFactory->create();
        $collection->setStoreId($storeId)
            ->addAttributeToSelect(['name', 'url_key', 'small_image', 'thumbnail', 'image', 'description'])
            ->addAttributeToFilter('type_id', ['in' => self::TYPES])
            ->addAttributeToFilter('status', 1)
            ->setVisibility($this->visibility->getVisibleInCatalogIds())
            ->addStoreFilter($store)
            ->addAttributeToSort('entity_id', 'desc');
        $collection->setPageSize($max);

        $products = [];
        foreach ($collection as $product) {
            $products[(int) $product->getId()] = $product;
        }
        if (!$products) {
            return ['items' => [], 'category_names' => []];
        }

        $allowed = array_flip($this->storefrontVisibility->searchableIds(
            $this->storefrontVisibility->sellableIds(array_keys($products), $storeId)
        ));
        $products = array_intersect_key($products, $allowed);
        if (!$products) {
            return ['items' => [], 'category_names' => []];
        }

        $ids = array_keys($products);
        [$categories, $categoryNames] = $this->loadTopCategories($ids, $storeId, (int) $store->getRootCategoryId());
        $options = $this->loadOptions($ids);
        $prices = $this->loadIndexPrices($ids, (int) $store->getWebsiteId());
        $children = $this->loadChildren($options, $storeId);
        $ratings = $this->loadRatings($ids);

        $items = [];
        foreach ($products as $id => $product) {
            $opts = $options[$id] ?? [];
            if (!$opts) {
                continue;   // a bundle with no options cannot be bought
            }
            $price = $prices[$id] ?? null;
            if ($price === null || $price['min'] <= 0) {
                continue;   // not price-indexed yet
            }

            $required = array_values(array_filter($opts, static fn (array $o): bool => (bool) $o['required']));
            $counted = $required ?: $opts;
            $isKit = count($counted) > 1;

            $regular = self::regularTotal($counted, $children, $isKit);
            $saving = $regular > 0 ? $regular - $price['min'] : 0.0;
            if ($saving < 0.005) {
                $saving = 0.0;
                $regular = 0.0;
            }

            $thumbIds = self::thumbIds($counted);
            $thumbs = [];
            foreach ($thumbIds as $childId) {
                if (isset($children[$childId]) && $children[$childId]['thumb'] !== null) {
                    $thumbs[] = $children[$childId]['thumb'];
                }
                if (count($thumbs) >= self::THUMB_LIMIT) {
                    break;
                }
            }
            $thumbTotal = $isKit ? count($counted) : (int) ($counted[0]['selection_count'] ?? 0);

            $items[] = [
                'id' => $id,
                'sku' => (string) $product->getSku(),
                'name' => (string) $product->getName(),
                'url_key' => (string) $product->getData('url_key'),
                'vendor_id' => (int) $product->getData('vendor_id'),
                'image_url' => $this->bannerUrl($product),
                'description' => self::excerpt((string) $product->getData('description'))
                    ?? self::describeContents($thumbIds, $children),
                'is_kit' => $isKit,
                'item_count' => $isKit ? count($counted) : (int) ($counted[0]['selection_count'] ?? 0),
                'thumbnails' => $thumbs,
                'more_thumbnails' => max(0, $thumbTotal - self::THUMB_LIMIT),
                'price' => $price['min'],
                'price_max' => $price['max'],
                'price_is_from' => $price['max'] > $price['min'] + 0.005,
                'regular_total' => $regular > 0 ? $regular : null,
                'saving' => $saving > 0 ? $saving : null,
                'discount_percent' => $saving > 0 && $regular > 0 ? (int) round($saving / $regular * 100) : 0,
                'rating_percent' => $ratings[$id]['pct'] ?? null,
                'review_count' => $ratings[$id]['count'] ?? 0,
                'category_ids' => $categories[$id] ?? [],
            ];
        }

        return ['items' => $items, 'category_names' => $categoryNames];
    }

    /**
     * "All Bundles" plus one chip per department that has a bundle, catalogue
     * order; empty when there are fewer than two departments (nothing to filter).
     *
     * @param array<int, array<string, mixed>> $items
     * @param array<int, string> $categoryNames
     * @return array<int, array{id: int, name: string, count: int}> departments only (no "All" row)
     */
    public static function categoryChips(array $items, array $categoryNames): array
    {
        $counts = [];
        foreach ($items as $item) {
            foreach ((array) ($item['category_ids'] ?? []) as $categoryId) {
                $counts[(int) $categoryId] = ($counts[(int) $categoryId] ?? 0) + 1;
            }
        }
        if (count($counts) < 2) {
            return [];
        }

        $chips = [];
        foreach ($categoryNames as $id => $name) {
            if (isset($counts[$id])) {
                $chips[] = ['id' => (int) $id, 'name' => (string) $name, 'count' => $counts[$id]];
            }
        }

        return $chips;
    }

    /**
     * Largest discount across the cards (0 hides the stat).
     *
     * @param array<int, array<string, mixed>> $items
     */
    public static function maxDiscount(array $items): int
    {
        $max = 0;
        foreach ($items as $item) {
            $max = max($max, (int) ($item['discount_percent'] ?? 0));
        }

        return $max;
    }

    /**
     * Distinct sellers across the cards (vendor 0, Hub Market, counts as one).
     *
     * @param array<int, array<string, mixed>> $items
     */
    public static function sellerCount(array $items): int
    {
        $sellers = [];
        foreach ($items as $item) {
            $sellers[(int) ($item['vendor_id'] ?? 0)] = true;
        }

        return count($sellers);
    }

    /**
     * What the same products cost bought separately, at their regular prices.
     *
     * @param array<int, array<string, mixed>> $options counted options
     * @param array<int, array<string, mixed>> $children
     */
    public static function regularTotal(array $options, array $children, bool $isKit): float
    {
        if (!$isKit) {
            //  A chooser: "From <min regular>" over the same choices.
            $minRegular = null;
            foreach (($options[0]['product_ids'] ?? []) as $childId) {
                $price = $children[$childId]['price'] ?? null;
                if ($price === null || $price <= 0) {
                    continue;
                }
                $minRegular = $minRegular === null ? $price : min($minRegular, $price);
            }

            return (float) ($minRegular ?? 0.0);
        }

        $total = 0.0;
        foreach ($options as $option) {
            $cheapest = null;
            foreach ($option['product_ids'] as $childId) {
                $price = $children[$childId]['price'] ?? null;
                if ($price === null || $price <= 0) {
                    continue;
                }
                $cheapest = $cheapest === null ? $price : min($cheapest, $price);
            }
            if ($cheapest === null) {
                return 0.0;   // incomplete data: no comparison rather than a wrong one
            }
            $total += $cheapest * (float) $option['qty'];
        }

        return $total;
    }

    /**
     * A kit shows one thumbnail per option; a chooser shows its choices.
     *
     * @param array<int, array<string, mixed>> $options
     * @return int[]
     */
    public static function thumbIds(array $options): array
    {
        if (count($options) > 1) {
            return array_values(array_filter(array_map(
                static fn (array $o): ?int => $o['product_ids'][0] ?? null,
                $options
            )));
        }

        return $options[0]['product_ids'] ?? [];
    }

    /**
     * First 140 characters of the text of $html, or null when there is none.
     */
    public static function excerpt(string $html): ?string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($html)) ?? '');
        if ($text === '') {
            return null;
        }
        if (mb_strlen($text) > 140) {
            $text = rtrim(mb_substr($text, 0, 140), " ,.;:-") . '…';
        }

        return $text;
    }

    /**
     * The bundle's contents in words, for a bundle with no description of its own.
     *
     * @param int[] $thumbIds
     * @param array<int, array<string, mixed>> $children
     */
    public static function describeContents(array $thumbIds, array $children): ?string
    {
        $names = [];
        foreach ($thumbIds as $childId) {
            $name = trim((string) ($children[$childId]['name'] ?? ''));
            if ($name !== '') {
                $names[] = $name;
            }
        }
        $names = array_values(array_unique($names));
        if (!$names) {
            return null;
        }
        $shown = array_slice($names, 0, 3);
        $more = count($names) - count($shown);
        $list = implode(', ', $shown);
        $text = $more > 0
            ? (string) __('Includes %1 and %2 more.', $list, $more)
            : (string) __('Includes %1.', $list);

        return self::excerpt($text);
    }

    /**
     * Options per bundle with selection count, child ids (position order) and minimum qty.
     *
     * @param int[] $parentIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function loadOptions(array $parentIds): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from(
                ['o' => $this->resource->getTableName('catalog_product_bundle_option')],
                ['option_id', 'parent_id', 'required']
            )
            ->joinLeft(
                ['s' => $this->resource->getTableName('catalog_product_bundle_selection')],
                's.option_id = o.option_id',
                [
                    'selection_count' => new \Zend_Db_Expr('COUNT(s.selection_id)'),
                    'product_ids' => new \Zend_Db_Expr('GROUP_CONCAT(s.product_id ORDER BY s.position, s.selection_id)'),
                    'qty' => new \Zend_Db_Expr('MIN(s.selection_qty)'),
                ]
            )
            ->where('o.parent_id IN (?)', $parentIds)
            ->group('o.option_id')
            ->order(['o.parent_id', 'o.position', 'o.option_id']);

        $out = [];
        foreach ($connection->fetchAll($select) as $row) {
            $out[(int) $row['parent_id']][] = [
                'required' => (int) $row['required'],
                'selection_count' => (int) $row['selection_count'],
                'product_ids' => array_values(array_filter(array_map('intval', explode(',', (string) $row['product_ids'])))),
                'qty' => max(1.0, (float) $row['qty']),
            ];
        }

        return $out;
    }

    /**
     * Indexed min/max price, guest group, of the store's website.
     *
     * @param int[] $ids
     * @return array<int, array{min: float, max: float}>
     */
    private function loadIndexPrices(array $ids, int $websiteId): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName('catalog_product_index_price'), ['entity_id', 'min_price', 'max_price'])
            ->where('entity_id IN (?)', $ids)
            ->where('website_id = ?', $websiteId)
            ->where('customer_group_id = ?', 0);

        $out = [];
        foreach ($connection->fetchAll($select) as $row) {
            $out[(int) $row['entity_id']] = ['min' => (float) $row['min_price'], 'max' => (float) $row['max_price']];
        }

        return $out;
    }

    /**
     * Thumbnail, REGULAR price and name of every child of every option, one collection.
     *
     * @param array<int, array<int, array<string, mixed>>> $options
     * @return array<int, array{thumb: string|null, price: float, name: string}>
     */
    private function loadChildren(array $options, int $storeId): array
    {
        $childIds = [];
        foreach ($options as $opts) {
            foreach ($opts as $option) {
                foreach ($option['product_ids'] as $childId) {
                    $childIds[$childId] = true;
                }
            }
        }
        if (!$childIds) {
            return [];
        }

        $children = $this->collectionFactory->create();
        $children->setStoreId($storeId)
            ->addAttributeToSelect(['name', 'small_image', 'thumbnail', 'image', 'price'])
            ->addIdFilter(array_keys($childIds))
            ->addStoreFilter($storeId);

        $out = [];
        foreach ($children as $child) {
            $file = (string) $child->getData('small_image');
            $thumb = null;
            if ($file !== '' && $file !== 'no_selection') {
                try {
                    $thumb = (string) $this->imageHelper->init($child, 'product_small_image')
                        ->keepFrame(false)
                        ->resize(96, 96)
                        ->getUrl();
                } catch (\Throwable $e) {
                    $this->logger->warning('HomeSections: bundle child image: ' . $e->getMessage());
                }
            }
            $out[(int) $child->getId()] = [
                'thumb' => $thumb,
                //  REGULAR price on purpose: the index min already reflects specials,
                //  so a child going on special turns into a real bundle saving.
                'price' => (float) $child->getData('price'),
                'name' => trim((string) $child->getName()),
            ];
        }

        return $out;
    }

    /**
     * 16:9 card image without the white frame the resizer adds by default.
     *
     * @param \Magento\Catalog\Model\Product $product
     */
    private function bannerUrl($product): ?string
    {
        $file = (string) $product->getData('small_image');
        if ($file === '' || $file === 'no_selection') {
            return null;
        }
        try {
            return (string) $this->imageHelper->init($product, 'category_page_grid')
                ->keepFrame(false)
                ->resize(800, 450)
                ->getUrl();
        } catch (\Throwable $e) {
            $this->logger->warning('HomeSections: bundle image: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Default-scope review summary per product (store-view rows are all 0 here).
     *
     * @param int[] $ids
     * @return array<int, array{pct: int|null, count: int}>
     */
    private function loadRatings(array $ids): array
    {
        try {
            $connection = $this->resource->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from($this->resource->getTableName('review_entity_summary'), ['entity_pk_value', 'rating_summary', 'reviews_count'])
                    ->where('entity_type = ?', 1)
                    ->where('store_id = ?', 0)
                    ->where('entity_pk_value IN (?)', $ids)
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HomeSections: bundle ratings: ' . $e->getMessage());

            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $count = (int) $row['reviews_count'];
            $out[(int) $row['entity_pk_value']] = [
                'pct' => $count > 0 ? (int) $row['rating_summary'] : null,
                'count' => $count,
            ];
        }

        return $out;
    }

    /**
     * Top-level ACTIVE categories per bundle, and their names in catalogue order.
     *
     * @param int[] $productIds
     * @return array{0: array<int, int[]>, 1: array<int, string>}
     */
    private function loadTopCategories(array $productIds, int $storeId, int $rootId): array
    {
        try {
            $connection = $this->resource->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from(['ccp' => $this->resource->getTableName('catalog_category_product')], ['product_id'])
                    ->join(
                        ['ce' => $this->resource->getTableName('catalog_category_entity')],
                        'ce.entity_id = ccp.category_id',
                        ['path']
                    )
                    ->where('ccp.product_id IN (?)', $productIds)
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HomeSections: bundle categories unavailable: ' . $e->getMessage());

            return [[], []];
        }

        $tops = [];
        foreach ($rows as $row) {
            //  path = 1/<root>/<top>/...: the department is the level under this store's root.
            $parts = explode('/', (string) $row['path']);
            if (count($parts) < 3 || (int) $parts[1] !== $rootId) {
                continue;
            }
            $top = (int) $parts[2];
            $tops[(int) $row['product_id']][$top] = $top;
        }

        $allTops = [];
        foreach ($tops as $set) {
            foreach ($set as $top) {
                $allTops[$top] = true;
            }
        }
        $names = $this->activeCategoryNames(array_keys($allTops), $storeId);

        $out = [];
        foreach ($tops as $productId => $set) {
            $out[$productId] = array_values(array_filter($set, static fn (int $id): bool => isset($names[$id])));
        }

        return [$out, $names];
    }

    /**
     * @param int[] $ids
     * @return array<int, string> id => name of the active ones, catalogue order
     */
    private function activeCategoryNames(array $ids, int $storeId): array
    {
        if (!$ids) {
            return [];
        }
        try {
            $nameAttr = (int) $this->eavConfig->getAttribute(Category::ENTITY, 'name')->getId();
            $activeAttr = (int) $this->eavConfig->getAttribute(Category::ENTITY, 'is_active')->getId();
        } catch (\Throwable $e) {
            return [];
        }
        if (!$nameAttr || !$activeAttr) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $varchar = $this->resource->getTableName('catalog_category_entity_varchar');
        $int = $this->resource->getTableName('catalog_category_entity_int');
        $select = $connection->select()
            ->from(['e' => $this->resource->getTableName('catalog_category_entity')], ['entity_id'])
            ->joinLeft(['nd' => $varchar], "nd.entity_id = e.entity_id AND nd.attribute_id = {$nameAttr} AND nd.store_id = 0", [])
            ->joinLeft(['ns' => $varchar], "ns.entity_id = e.entity_id AND ns.attribute_id = {$nameAttr} AND ns.store_id = {$storeId}", [])
            ->joinLeft(['ad' => $int], "ad.entity_id = e.entity_id AND ad.attribute_id = {$activeAttr} AND ad.store_id = 0", [])
            ->joinLeft(['as_' => $int], "as_.entity_id = e.entity_id AND as_.attribute_id = {$activeAttr} AND as_.store_id = {$storeId}", [])
            ->columns(['name' => new \Zend_Db_Expr('COALESCE(ns.value, nd.value)')])
            ->where('e.entity_id IN (?)', $ids)
            ->where('COALESCE(as_.value, ad.value) = 1')
            ->order('e.position ASC')
            ->order('e.entity_id ASC');

        $out = [];
        foreach ($connection->fetchPairs($select) as $id => $name) {
            if (trim((string) $name) !== '') {
                $out[(int) $id] = (string) $name;
            }
        }

        return $out;
    }
}
