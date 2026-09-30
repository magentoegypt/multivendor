<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Resolver\Identity;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;

/**
 * Cache tags of hmStoreCategories: hm_vendor (a seller joining, leaving or
 * changing moves the counts) and cat_c_<id> of every chip (a renamed or
 * hidden category).
 */
class StoreCategoriesIdentity implements IdentityInterface
{
    /**
     * @inheritDoc
     */
    public function getIdentities(array $resolvedData): array
    {
        $tags = [Tags::VENDOR];
        foreach ((array) ($resolvedData['items'] ?? []) as $item) {
            $id = (int) ($item['id'] ?? 0);
            if ($id > 0) {
                $tags[] = Tags::category($id);
            }
        }

        return array_values(array_unique($tags));
    }
}
