<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver\Identity;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;

/**
 * Cache tag of one ProductInterface.hm_seller value: hm_vendor_<id>, purged when
 * that seller, their settings or a review of their products is saved. Hub
 * Market (vendor 0) and a null seller add no tag; the product's own cat_p tags
 * already cover the response.
 */
class SellerIdentity implements IdentityInterface
{
    /**
     * @inheritDoc
     */
    public function getIdentities(array $resolvedData): array
    {
        $vendorId = (int) ($resolvedData['vendor_entity_id'] ?? 0);

        return $vendorId > 0 ? [Tags::vendor($vendorId)] : [];
    }
}
