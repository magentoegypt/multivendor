<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Cache;

/**
 * Cache tags of the HubApp family, in one place.
 *
 * The same tags go on HTTP cache entries (GraphQL GET, through the resolvers'
 * cache identities) and on `hubapp` app-cache entries, so one purge clears both
 * (Api\CacheTagCleanerInterface).
 *
 * | Tag                      | Carried by                        | Purged by                                   |
 * |--------------------------|-----------------------------------|---------------------------------------------|
 * | hm_app_config            | hmAppConfig                       | config save (sections hubapp, algolia)      |
 * | hm_app_home, _<id>       | hmAppHome                         | section save/delete, hero banner save, cron |
 * | hm_app_catalog           | deals, best sellers, bundles      | cron (midnight, catalogue/order movement)   |
 * | hm_brand                 | hmBrands, TOP_BRANDS              | MGS brand save/delete                       |
 * | hm_vendor, hm_vendor_<id>| stores, hm_seller (HubAppVendors) | vendor/config/review saves (HubAppVendors)  |
 * | cat_p, cat_p_<id>        | products in any list              | core product save                           |
 * | cat_c_<id>               | category chips                    | core category save                          |
 * | cms_b_<id>, cms_b_<code> | CMS-backed sections               | core CMS block save                         |
 *
 * App-cache entries never carry cat_p / cat_p_<id>: core cleans `cat_p` from the
 * app cache on EVERY product save (AbstractModel::cleanModelCache), and the
 * Odoo sync saves products all day. Product-driven staleness of a cached ranking
 * is bounded by its TTL and by the cron's catalogue check instead; product DATA
 * (name, price, stock) is never cached here, it is loaded per request.
 *
 * Plain class constants (PHP 8.2 has no typed constants).
 */
final class Tags
{
    public const APP_CONFIG = 'hm_app_config';
    public const APP_HOME = 'hm_app_home';
    public const APP_HOME_SECTION_PREFIX = 'hm_app_home_';
    public const APP_CATALOG = 'hm_app_catalog';
    public const BRAND = 'hm_brand';
    public const VENDOR = 'hm_vendor';
    public const VENDOR_PREFIX = 'hm_vendor_';

    /** Core tags (Magento\Catalog\Model\Product::CACHE_TAG and friends). */
    public const PRODUCT = 'cat_p';
    public const PRODUCT_PREFIX = 'cat_p_';
    public const CATEGORY = 'cat_c';
    public const CATEGORY_PREFIX = 'cat_c_';
    public const CMS_BLOCK = 'cms_b';
    public const CMS_BLOCK_PREFIX = 'cms_b_';

    /** Everything a full reset of the app's caches should clear. */
    public const ALL = [
        self::APP_CONFIG,
        self::APP_HOME,
        self::APP_CATALOG,
        self::BRAND,
        self::VENDOR,
    ];

    private function __construct()
    {
    }

    public static function homeSection(int $sectionId): string
    {
        return self::APP_HOME_SECTION_PREFIX . $sectionId;
    }

    public static function vendor(int $vendorEntityId): string
    {
        return self::VENDOR_PREFIX . $vendorEntityId;
    }

    public static function product(int $productId): string
    {
        return self::PRODUCT_PREFIX . $productId;
    }

    public static function category(int $categoryId): string
    {
        return self::CATEGORY_PREFIX . $categoryId;
    }

    /**
     * Both tags core puts on a CMS block (by id and by identifier).
     *
     * @return string[]
     */
    public static function cmsBlock(int $blockId, string $identifier): array
    {
        return [self::CMS_BLOCK_PREFIX . $blockId, self::CMS_BLOCK_PREFIX . $identifier];
    }

    /**
     * Product tags for a list of ids, generic tag first (as core's product identity does).
     *
     * @param int[] $productIds
     * @return string[]
     */
    public static function products(array $productIds): array
    {
        $tags = [];
        foreach ($productIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $tags[] = self::product($id);
            }
        }

        return $tags ? array_values(array_unique(array_merge([self::PRODUCT], $tags))) : [];
    }

    /**
     * Tags that are safe on an app-cache entry (see the class note on cat_p).
     *
     * @param string[] $tags
     * @return string[]
     */
    public static function forAppCache(array $tags): array
    {
        return array_values(array_unique(array_filter(
            $tags,
            static fn ($tag): bool => is_string($tag) && $tag !== ''
                && $tag !== self::PRODUCT && !str_starts_with($tag, self::PRODUCT_PREFIX)
        )));
    }
}
