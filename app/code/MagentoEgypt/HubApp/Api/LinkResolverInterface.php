<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Api;

/**
 * Turns the destinations merchandisers type in admin ("clothes.html", "shop/loly",
 * "bundles", a full URL) into HmLink values the app can route natively.
 *
 * Every method returns an HmLink-shaped array:
 *   [
 *     'type' => one of the TYPE_* constants (the HmLinkType enum name),
 *     'url'  => absolute storefront URL, always set (the app's fallback),
 *     'path' => store-relative path as configured, or null,
 *     'uid'  => entity uid for CATEGORY / PRODUCT, or null,
 *     'code' => seller code, brand url_key, CMS identifier, product url_key
 *               or search text, or null,
 *   ]
 *
 * Classification order for free-text targets: http(s) -> EXTERNAL (unless it is
 * this store's own base URL, which is then classified by its path); the seller
 * page route ("shop/<code>") -> STORE; the seller list -> STORES; the MGS brand
 * route -> BRAND / BRANDS; "bundles" -> BUNDLES; "deals" -> DEALS;
 * catalogsearch/result?q= -> SEARCH; otherwise url_rewrite for the store ->
 * CATEGORY / PRODUCT / CMS_PAGE; anything left is EXTERNAL with the absolute URL.
 *
 * Batch-first: resolveMany() classifies a whole Home in one url_rewrite query.
 */
interface LinkResolverInterface
{
    public const TYPE_CATEGORY = 'CATEGORY';
    public const TYPE_PRODUCT = 'PRODUCT';
    public const TYPE_CMS_PAGE = 'CMS_PAGE';
    public const TYPE_STORE = 'STORE';
    public const TYPE_STORES = 'STORES';
    public const TYPE_BRAND = 'BRAND';
    public const TYPE_BRANDS = 'BRANDS';
    public const TYPE_BUNDLES = 'BUNDLES';
    public const TYPE_DEALS = 'DEALS';
    public const TYPE_SEARCH = 'SEARCH';
    public const TYPE_EXTERNAL = 'EXTERNAL';

    /**
     * Classify one configured destination; null when it is empty.
     *
     * @return array<string, string|null>|null
     */
    public function resolve(?string $target, int $storeId): ?array;

    /**
     * Classify many destinations with one url_rewrite lookup.
     *
     * @param array<int|string, string|null> $targets
     * @return array<int|string, array<string, string|null>|null> same keys as $targets
     */
    public function resolveMany(array $targets, int $storeId): array;

    /**
     * Link to a category whose id and request path are already known (no lookup).
     *
     * @return array<string, string|null>
     */
    public function category(int $categoryId, ?string $requestPath, int $storeId): array;

    /**
     * Link to a product whose id, url_key and request path are already known (no lookup).
     *
     * @return array<string, string|null>
     */
    public function product(int $productId, string $urlKey, ?string $requestPath, int $storeId): array;

    /**
     * Link to a seller's store page (vendors/vendorspage/url_key + "/" + code).
     *
     * @return array<string, string|null>
     */
    public function store(string $sellerCode, int $storeId): array;

    /**
     * Link to an MGS brand page (brand/general_settings/route + "/" + url_key).
     *
     * @return array<string, string|null>
     */
    public function brand(string $urlKey, int $storeId): array;
}
