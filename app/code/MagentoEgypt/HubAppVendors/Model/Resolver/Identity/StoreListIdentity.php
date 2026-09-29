<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver\Identity;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;

/**
 * Cache tags of an hmStores page: hm_vendor (any seller change can reorder or
 * refill a list) plus hm_vendor_<id> of every card on it.
 */
class StoreListIdentity implements IdentityInterface
{
    /**
     * @inheritDoc
     */
    public function getIdentities(array $resolvedData): array
    {
        $tags = [Tags::VENDOR];
        foreach ((array) ($resolvedData['items'] ?? []) as $card) {
            $vendorId = (int) ($card['vendor_entity_id'] ?? 0);
            if ($vendorId > 0) {
                $tags[] = Tags::vendor($vendorId);
            }
        }

        return array_values(array_unique($tags));
    }
}
