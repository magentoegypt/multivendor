<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Model\CategoryChip;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

/**
 * "Shop by category" chips as data — Block\CategoryChips::getCategories(),
 * with the layout arguments (limit, order, icons, tints) as parameters so the
 * app's CATEGORY_CHIPS section reads the same rules from its admin options.
 *
 * Rules, as on the website:
 *   - top-level (level 2), active, in the menu, with at least one product
 *     (live count from the category collection: children included);
 *   - `order` (URL keys) is both an allow-list and the sort; a key that does
 *     not exist is skipped, so a chip appears by itself once its category is
 *     created; without `order` the catalogue position order, cut to `limit`;
 *   - glyph and tint keyed on URL KEY (the same in every store view); a chip
 *     with no configured tint takes its position modulo 8.
 *
 * `request_path` (the category's URL rewrite in the store) is returned instead
 * of a built URL so each caller links it its own way.
 */
class CategoryChipReader
{
    public function __construct(private readonly CollectionFactory $collectionFactory)
    {
    }

    /**
     * @param string[] $order URL keys, allow-list and order; empty for catalogue order
     * @param array<string, string> $icons URL key => glyph
     * @param array<string, int|string> $tints URL key => slot 0-7
     * @param string|null $fallbackIcon glyph for an unmapped key (the website uses a tag emoji; the app null)
     * @return array<int, array{id: int, url_key: string, name: string, request_path: string|null, count: int, icon: string|null, tint: int}>
     */
    public function getChips(
        int $storeId,
        int $limit,
        array $order = [],
        array $icons = [],
        array $tints = [],
        ?string $fallbackIcon = null
    ): array {
        $limit = max(1, $limit);
        $order = array_values(array_filter(array_map(
            static fn ($key): string => strtolower(trim((string) $key)),
            $order
        )));

        $collection = $this->collectionFactory->create();
        $collection->setStoreId($storeId)
            ->addAttributeToSelect(['name', 'url_key'])
            ->addAttributeToFilter('is_active', 1)
            ->addAttributeToFilter('include_in_menu', 1)
            ->addFieldToFilter('level', 2)
            ->setLoadProductCount(true)
            ->addAttributeToSort('position', 'ASC');
        $collection->addUrlRewriteToResult();
        if ($order) {
            $collection->addAttributeToFilter('url_key', ['in' => $order]);
        }

        $out = [];
        $index = 0;
        foreach ($collection as $category) {
            $count = (int) $category->getProductCount();
            if ($count < 1) {
                continue;
            }
            $urlKey = (string) $category->getUrlKey();
            $key = strtolower($urlKey);
            $requestPath = trim((string) $category->getData('request_path'));
            $out[] = [
                'id' => (int) $category->getId(),
                'url_key' => $urlKey,
                'name' => (string) $category->getName(),
                'request_path' => $requestPath !== '' ? $requestPath : null,
                'count' => $count,
                'icon' => isset($icons[$key]) && (string) $icons[$key] !== '' ? (string) $icons[$key] : $fallbackIcon,
                'tint' => array_key_exists($key, $tints) ? ((int) $tints[$key]) % 8 : $index % 8,
            ];
            $index++;
            if (!$order && count($out) >= $limit) {
                break;
            }
        }

        //  Sorted here, not in SQL: IN () keeps no order, and FIELD() is MySQL-only.
        if ($order) {
            $rank = array_flip($order);
            usort($out, static fn (array $a, array $b): int
                => ($rank[strtolower($a['url_key'])] ?? PHP_INT_MAX) <=> ($rank[strtolower($b['url_key'])] ?? PHP_INT_MAX));
            $out = array_slice($out, 0, $limit);
        }

        return $out;
    }
}
