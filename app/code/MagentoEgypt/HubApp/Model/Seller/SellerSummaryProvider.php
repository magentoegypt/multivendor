<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Seller;

use MagentoEgypt\HubApp\Api\SellerSummaryProviderInterface;

/**
 * STUB — owned by HubAppVendors (owner B), who replaces this file.
 *
 * Until then it answers only for Hub Market itself (vendor 0) and leaves every
 * seller out, so `seller` / `hm_seller` fields are null for vendor products
 * instead of showing an unapproved or unnamed seller. The real implementation
 * (see the design, §3 "SellerSummaryProvider") batches VendorNames, VendorMeta,
 * ves_vendor_config logos and the listable counts, and requires status 2.
 */
class SellerSummaryProvider implements SellerSummaryProviderInterface
{
    public const MARKETPLACE_NAME = 'Hub Market';

    /**
     * @inheritDoc
     */
    public function getByVendorIds(array $vendorIds, int $storeId): array
    {
        $out = [];
        foreach ($vendorIds as $vendorId) {
            if ((int) $vendorId === 0) {
                $out[0] = $this->getMarketplace($storeId);
            }
        }

        return $out;
    }

    /**
     * @inheritDoc
     */
    public function getMarketplace(int $storeId): array
    {
        return [
            'code' => null,
            'vendor_entity_id' => null,
            //  Latin in both locales, as in the storefront header and VendorNames::getName().
            'name' => self::MARKETPLACE_NAME,
            'logo_url' => null,
            'rating' => null,
            'review_count' => 0,
            'product_count' => 0,
            'is_marketplace' => true,
            'link' => null,
        ];
    }
}
