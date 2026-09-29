<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Api;

/**
 * "Who sells this" for products, cart and order lines, bundles and returns
 * (GraphQL type HmSellerSummary).
 *
 * Implemented by HubAppVendors (owner B) in Model\Seller\SellerSummaryProvider;
 * the core module ships only a stub so the schema and callers work before it
 * lands. Callers MUST batch: collect every vendor id of a response and ask once.
 *
 * Each summary is an HmSellerSummary-shaped array:
 *   [
 *     'code'             => seller code (ves_vendor_entity.vendor_id) or null for Hub Market,
 *     'vendor_entity_id' => ves_vendor_entity.entity_id or null for Hub Market,
 *     'name'             => display name in the store view's language (never empty),
 *     'logo_url'         => absolute https logo or null,
 *     'rating'           => float out of 5, one decimal, or null when unrated,
 *     'review_count'     => int,
 *     'product_count'    => int,
 *     'is_marketplace'   => bool,
 *     'link'             => HmLink array (STORE) or null for Hub Market,
 *   ]
 */
interface SellerSummaryProviderInterface
{
    /** Keys of one summary, in schema order. */
    public const FIELDS = [
        'code',
        'vendor_entity_id',
        'name',
        'logo_url',
        'rating',
        'review_count',
        'product_count',
        'is_marketplace',
        'link',
    ];

    /**
     * Summaries keyed by vendor entity id.
     *
     * 0 (admin-owned products) maps to the Hub Market summary. Ids of sellers
     * that are missing or not approved (status 2) are ABSENT from the result;
     * the GraphQL field is then null.
     *
     * @param int[] $vendorIds
     * @return array<int, array<string, mixed>>
     */
    public function getByVendorIds(array $vendorIds, int $storeId): array;

    /**
     * The Hub Market summary (vendor 0).
     *
     * @return array<string, mixed>
     */
    public function getMarketplace(int $storeId): array;
}
