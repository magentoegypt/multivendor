<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Seller;

use MagentoEgypt\HubApp\Api\LinkResolverInterface;
use MagentoEgypt\HubApp\Api\MediaUrlInterface;
use MagentoEgypt\HubApp\Api\SellerSummaryProviderInterface;

/**
 * HmSellerSummary values ("sold by") for products, cart and order lines, bundles and returns.
 *
 * Batched: a call covers every seller of a response with a fixed number of
 * queries whatever the number of sellers —
 *   - the seller table, once per request (SellerDirectory);
 *   - names for all sellers in one storefront emulation (theme translations);
 *   - ratings (SellerRatings, the website's VendorMeta query);
 *   - logos (VendorConfigReader, ves_vendor_config);
 *   - store-page product counts (ListableProducts, the storefront gate).
 * Summaries are kept for the rest of the request, so a later call for the same
 * sellers (cart items, then their products) costs nothing.
 *
 * Rules (design §3, §9.2): only APPROVED sellers (status 2) get a summary;
 * missing or unapproved sellers are absent and the field is null. Vendor 0 —
 * products created in admin — is Hub Market itself.
 */
class SellerSummaryProvider implements SellerSummaryProviderInterface
{
    public const MARKETPLACE_NAME = SellerName::MARKETPLACE;

    /** @var array<int, array<int, array<string, mixed>|null>> store id => vendor id => summary, null = none */
    private array $built = [];

    public function __construct(
        private readonly SellerDirectory $directory,
        private readonly SellerRatings $ratings,
        private readonly ListableProducts $listableProducts,
        private readonly VendorConfigReader $vendorConfig,
        private readonly MediaUrlInterface $mediaUrl,
        private readonly LinkResolverInterface $linkResolver
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getByVendorIds(array $vendorIds, int $storeId): array
    {
        $ids = [];
        $marketplace = false;
        foreach ($vendorIds as $vendorId) {
            $vendorId = (int) $vendorId;
            if ($vendorId === 0) {
                $marketplace = true;
            } elseif ($vendorId > 0) {
                $ids[$vendorId] = $vendorId;
            }
        }

        $missing = array_values(array_diff_key($ids, $this->built[$storeId] ?? []));
        if ($missing) {
            $this->build($missing, $storeId);
        }

        $out = [];
        if ($marketplace) {
            $out[0] = $this->getMarketplace($storeId);
        }
        foreach ($ids as $vendorId) {
            $summary = $this->built[$storeId][$vendorId] ?? null;
            if ($summary !== null) {
                $out[$vendorId] = $summary;
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
            'name' => SellerName::MARKETPLACE,
            'logo_url' => null,
            'rating' => null,
            'review_count' => 0,
            'product_count' => 0,
            'is_marketplace' => true,
            'link' => null,
        ];
    }

    /**
     * @param int[] $vendorIds
     */
    private function build(array $vendorIds, int $storeId): void
    {
        $approved = [];
        foreach ($vendorIds as $vendorId) {
            $this->built[$storeId][$vendorId] = null;
            $vendor = $this->directory->getApproved($vendorId);
            if ($vendor !== null) {
                $approved[$vendorId] = $vendor;
            }
        }
        if (!$approved) {
            return;
        }

        $ids = array_keys($approved);
        $names = $this->directory->names($ids, $storeId);
        $ratings = $this->ratings->forVendors($ids);
        $counts = $this->listableProducts->counts($ids, $storeId);
        $logos = $this->vendorConfig->read($ids, [VendorConfigReader::LOGO], $storeId);

        foreach ($approved as $vendorId => $vendor) {
            $this->built[$storeId][$vendorId] = [
                'code' => $vendor['code'],
                'vendor_entity_id' => $vendorId,
                'name' => $names[$vendorId] ?? SellerName::source($vendor['company'], $vendor['code']),
                'logo_url' => $this->mediaUrl->sellerLogo($logos[$vendorId][VendorConfigReader::LOGO] ?? null, $storeId),
                'rating' => $ratings[$vendorId]['rating'] ?? null,
                'review_count' => (int) ($ratings[$vendorId]['review_count'] ?? 0),
                'product_count' => (int) ($counts[$vendorId] ?? 0),
                'is_marketplace' => false,
                'link' => $this->linkResolver->store($vendor['code'], $storeId),
            ];
        }
    }
}
