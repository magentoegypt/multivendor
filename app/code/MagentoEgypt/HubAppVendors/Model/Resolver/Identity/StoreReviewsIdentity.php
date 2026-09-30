<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver\Identity;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;

/**
 * Cache tags of an hmStoreReviews page: hm_vendor_<id> (a review of one of the
 * seller's products becoming, staying or stopping being approved — see
 * CleanVendorCache — and the seller's own saves) and cat_p_<id> of every
 * product on the page (a renamed product or a new thumbnail). A null page has
 * no tags, so it is not cached at all.
 */
class StoreReviewsIdentity implements IdentityInterface
{
    /**
     * @inheritDoc
     */
    public function getIdentities(array $resolvedData): array
    {
        $vendorId = (int) ($resolvedData['vendor_entity_id'] ?? 0);
        if ($vendorId < 1) {
            return [];
        }
        $productIds = [];
        foreach ((array) ($resolvedData['items'] ?? []) as $item) {
            $productIds[] = (int) ($item['product_id'] ?? 0);
        }

        return array_values(array_unique(array_merge([Tags::vendor($vendorId)], Tags::products($productIds))));
    }
}
