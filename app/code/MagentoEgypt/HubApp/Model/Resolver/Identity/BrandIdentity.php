<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver\Identity;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;

/**
 * HTTP cache tag of hmBrands: hm_brand, purged by Plugin\MgsBrand\CleanBrandCache
 * whenever a brand is saved or deleted in admin.
 */
class BrandIdentity implements IdentityInterface
{
    /**
     * @param array<mixed> $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        return [Tags::BRAND];
    }
}
