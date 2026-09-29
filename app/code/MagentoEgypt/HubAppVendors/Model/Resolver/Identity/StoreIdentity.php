<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver\Identity;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;

/**
 * Cache tags of an hmStore page: hm_vendor_<id> (the seller's own saves and
 * reviews) and hm_vendor (the periodic catalogue purge that refreshes product
 * counts). A null page has no tags, so it is not cached at all.
 */
class StoreIdentity implements IdentityInterface
{
    /**
     * @inheritDoc
     */
    public function getIdentities(array $resolvedData): array
    {
        $vendorId = (int) ($resolvedData['card']['vendor_entity_id'] ?? 0);

        return $vendorId > 0 ? [Tags::VENDOR, Tags::vendor($vendorId)] : [];
    }
}
