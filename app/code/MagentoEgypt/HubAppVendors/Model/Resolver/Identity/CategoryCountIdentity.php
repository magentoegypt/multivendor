<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver\Identity;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;

/**
 * Cache tag of an HmStoreCard.primary_category value: cat_c_<id>, so a renamed
 * category leaves every cached response that names it. No category, no tag.
 */
class CategoryCountIdentity implements IdentityInterface
{
    /**
     * @inheritDoc
     */
    public function getIdentities(array $resolvedData): array
    {
        $id = (int) ($resolvedData['id'] ?? 0);

        return $id > 0 ? [Tags::category($id)] : [];
    }
}
