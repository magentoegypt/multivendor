<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Home section types — the admin "Type" select and the GraphQL enum HmSectionType.
 *
 * The values ARE the enum values; keep this list and the enum in
 * etc/schema.graphqls identical. A row whose type is not listed here is never
 * sent to the app (an unknown enum value would fail the whole response).
 */
class SectionType implements OptionSourceInterface
{
    public const DELIVERY_STRIP = 'DELIVERY_STRIP';
    public const HERO_BANNERS = 'HERO_BANNERS';
    public const CATEGORY_CHIPS = 'CATEGORY_CHIPS';
    public const TODAYS_DEALS = 'TODAYS_DEALS';
    public const PICKED_FOR_YOU = 'PICKED_FOR_YOU';
    public const FEATURED_STORES = 'FEATURED_STORES';
    public const CATEGORY_RAIL = 'CATEGORY_RAIL';
    public const BUNDLE_DEALS = 'BUNDLE_DEALS';
    public const CMS_PROMOS = 'CMS_PROMOS';
    public const BEST_SELLERS = 'BEST_SELLERS';
    public const POPULAR_PRODUCTS = 'POPULAR_PRODUCTS';
    public const TOP_BRANDS = 'TOP_BRANDS';
    public const TOP_VENDORS = 'TOP_VENDORS';
    public const NEW_STORES = 'NEW_STORES';
    public const TRUST_ROW = 'TRUST_ROW';
    public const CMS_BLOCK = 'CMS_BLOCK';
    public const PRODUCT_LIST = 'PRODUCT_LIST';
    public const ACTIVE_ORDER = 'ACTIVE_ORDER';

    /**
     * In the order the admin select shows them (the website's Home order; the
     * active-order card, which the website does not have, where Figma 07 puts it).
     */
    public const ALL = [
        self::DELIVERY_STRIP,
        self::ACTIVE_ORDER,
        self::HERO_BANNERS,
        self::CATEGORY_CHIPS,
        self::TODAYS_DEALS,
        self::PICKED_FOR_YOU,
        self::FEATURED_STORES,
        self::CATEGORY_RAIL,
        self::BUNDLE_DEALS,
        self::CMS_PROMOS,
        self::BEST_SELLERS,
        self::POPULAR_PRODUCTS,
        self::TOP_BRANDS,
        self::TOP_VENDORS,
        self::NEW_STORES,
        self::TRUST_ROW,
        self::CMS_BLOCK,
        self::PRODUCT_LIST,
    ];

    /** Types whose content is a CMS block, with the block they read when none is chosen. */
    public const CMS_DEFAULTS = [
        self::DELIVERY_STRIP => 'hm_delivery_promise',
        self::CMS_PROMOS => 'hm_home_promos',
        self::TRUST_ROW => 'hm_home_trust',
        self::CMS_BLOCK => null,
    ];

    /** Types that return products (the `products` field). */
    public const PRODUCT_TYPES = [
        self::TODAYS_DEALS,
        self::PICKED_FOR_YOU,
        self::CATEGORY_RAIL,
        self::BEST_SELLERS,
        self::POPULAR_PRODUCTS,
        self::PRODUCT_LIST,
    ];

    /** Types built by HubAppVendors (seller cards, the `stores` field). */
    public const STORE_TYPES = [
        self::FEATURED_STORES,
        self::TOP_VENDORS,
        self::NEW_STORES,
    ];

    /**
     * Placement-only types: the admin chooses WHERE the app draws something of
     * the viewer's own, and the section carries no content. The built Home is
     * shared by every viewer of a store view and audience, so what goes there
     * (the signed-in customer's open order) is read by the app itself.
     */
    public const PLACEMENT_TYPES = [
        self::ACTIVE_ORDER,
    ];

    public static function isKnown(string $type): bool
    {
        return in_array(strtoupper($type), self::ALL, true);
    }

    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        $labels = [
            self::DELIVERY_STRIP => __('Delivery promise strip (CMS block)'),
            self::HERO_BANNERS => __('Hero banners (Content > Hero Banner)'),
            self::CATEGORY_CHIPS => __('Shop by category chips'),
            self::TODAYS_DEALS => __("Today's Deals (live special prices)"),
            self::PICKED_FOR_YOU => __('Picked For You (top rated)'),
            self::FEATURED_STORES => __('Featured stores (chosen sellers)'),
            self::CATEGORY_RAIL => __('Category rail (products of one category)'),
            self::BUNDLE_DEALS => __('Bundle deals'),
            self::CMS_PROMOS => __('Promo cards (CMS block)'),
            self::BEST_SELLERS => __('Best sellers'),
            self::POPULAR_PRODUCTS => __('Popular products (catalogue rail)'),
            self::TOP_BRANDS => __('Top brands'),
            self::TOP_VENDORS => __('Top vendors (highest rated sellers)'),
            self::NEW_STORES => __('New stores (recently joined sellers)'),
            self::TRUST_ROW => __('Trust row (CMS block)'),
            self::CMS_BLOCK => __('Any CMS block'),
            self::PRODUCT_LIST => __('Hand-picked products (SKUs)'),
            self::ACTIVE_ORDER => __("Active order card (the signed-in customer's open order)"),
        ];

        $options = [];
        foreach (self::ALL as $type) {
            $options[] = ['value' => $type, 'label' => $labels[$type]];
        }

        return $options;
    }
}
