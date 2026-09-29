<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Catalog;

use Magento\Framework\GraphQl\Query\Uid;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Api\Data\StoreInterface;
use MagentoEgypt\HomeSections\Model\BundleDeal\BundleDealBuilder;
use MagentoEgypt\HubApp\Api\MediaUrlInterface;
use MagentoEgypt\HubApp\Api\SellerSummaryProviderInterface;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use MagentoEgypt\HubApp\Model\Cache\Tags;

/**
 * Bundle cards for the app (hmBundleDeals, BUNDLE_DEALS).
 *
 * The cards come from HomeSections' BundleDealBuilder — the website's bundle
 * rail rules — built inside storefront emulation (image ids from the theme,
 * "Includes …" in the store's language) and kept in the `hubapp` cache per
 * store view. Money is converted to the request's currency and sellers are
 * resolved in one batch at response time, since both can differ per request.
 */
class BundleCards
{
    public function __construct(
        private readonly BundleDealBuilder $builder,
        private readonly StorefrontEmulationInterface $emulation,
        private readonly MediaUrlInterface $mediaUrl,
        private readonly SellerSummaryProviderInterface $sellers,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly Uid $uid,
        private readonly AppCache $appCache
    ) {
    }

    /**
     * Raw cards (newest first) and the names of their top-level categories.
     *
     * @return array{items: array<int, array<string, mixed>>, category_names: array<int, string>}
     */
    public function get(int $storeId): array
    {
        $key = 'bundles_' . $storeId;
        $cached = $this->appCache->load($key);
        if ($cached !== null && isset($cached['items'])) {
            //  JSON turned the int keys of category_names into strings; restore them.
            $names = [];
            foreach ((array) ($cached['category_names'] ?? []) as $id => $name) {
                $names[(int) $id] = (string) $name;
            }

            return ['items' => (array) $cached['items'], 'category_names' => $names];
        }

        $data = $this->emulation->run($storeId, fn (): array => $this->builder->build($storeId));
        foreach ($data['items'] as &$item) {
            $item['image_url'] = $item['image_url'] !== null ? $this->mediaUrl->secure((string) $item['image_url'], $storeId) : null;
            $item['thumbnails'] = array_map(
                fn (string $url): string => $this->mediaUrl->secure($url, $storeId),
                (array) $item['thumbnails']
            );
        }
        unset($item);

        //  An ordered list survives JSON; an int-keyed map keeps its order too.
        $this->appCache->save($key, $data, [Tags::APP_CATALOG], AppCache::MAX_TTL);

        return $data;
    }

    /**
     * Cards whose top-level categories include $categoryId (null: all).
     *
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    public static function inCategory(array $items, ?int $categoryId): array
    {
        if ($categoryId === null) {
            return $items;
        }

        return array_values(array_filter(
            $items,
            static fn (array $item): bool => in_array($categoryId, array_map('intval', (array) ($item['category_ids'] ?? [])), true)
        ));
    }

    /**
     * HmBundleDeal values, sellers batched.
     *
     * @param array<int, array<string, mixed>> $items raw cards
     * @return array<int, array<string, mixed>>
     */
    public function toGraphQl(array $items, StoreInterface $store): array
    {
        if (!$items) {
            return [];
        }
        $storeId = (int) $store->getId();
        $vendorIds = array_values(array_unique(array_map(
            static fn (array $item): int => (int) ($item['vendor_id'] ?? 0),
            $items
        )));
        $sellers = $this->sellers->getByVendorIds($vendorIds, $storeId);
        $currency = (string) $store->getCurrentCurrencyCode();

        $out = [];
        foreach ($items as $item) {
            $out[] = [
                'uid' => $this->uid->encode((string) $item['id']),
                'sku' => (string) $item['sku'],
                'name' => (string) $item['name'],
                'url_key' => (string) $item['url_key'],
                'image_url' => $item['image_url'],
                'seller' => $sellers[(int) ($item['vendor_id'] ?? 0)] ?? null,
                'description' => $item['description'],
                'is_kit' => (bool) $item['is_kit'],
                'item_count' => (int) $item['item_count'],
                'thumbnails' => array_values((array) $item['thumbnails']),
                'more_thumbnails' => (int) $item['more_thumbnails'],
                'price' => $this->money((float) $item['price'], $store, $currency),
                'price_is_from' => (bool) $item['price_is_from'],
                'regular_total' => $item['regular_total'] !== null ? $this->money((float) $item['regular_total'], $store, $currency) : null,
                'saving' => $item['saving'] !== null ? $this->money((float) $item['saving'], $store, $currency) : null,
                'discount_percent' => (int) $item['discount_percent'],
                'rating_percent' => $item['rating_percent'] !== null ? (int) $item['rating_percent'] : null,
                'review_count' => (int) $item['review_count'],
                'category_ids' => array_values(array_map('intval', (array) $item['category_ids'])),
            ];
        }

        return $out;
    }

    /**
     * HmCategoryCount chips; empty when fewer than two departments.
     *
     * @param array<int, array<string, mixed>> $items all cards (unfiltered)
     * @param array<int, string> $categoryNames
     * @return array<int, array<string, mixed>>
     */
    public function categoryCounts(array $items, array $categoryNames): array
    {
        $out = [];
        foreach (BundleDealBuilder::categoryChips($items, $categoryNames) as $chip) {
            $out[] = [
                'id' => $chip['id'],
                'uid' => $this->uid->encode((string) $chip['id']),
                'name' => $chip['name'],
                'count' => $chip['count'],
            ];
        }

        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return int[]
     */
    public static function ids(array $items): array
    {
        return array_values(array_map(static fn (array $item): int => (int) $item['id'], $items));
    }

    /**
     * @return array{value: float, currency: string}
     */
    private function money(float $baseAmount, StoreInterface $store, string $currency): array
    {
        return [
            'value' => round((float) $this->priceCurrency->convert($baseAmount, $store), 2),
            'currency' => $currency,
        ];
    }
}
