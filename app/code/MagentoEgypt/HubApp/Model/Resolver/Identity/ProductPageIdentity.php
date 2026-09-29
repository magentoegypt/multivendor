<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver\Identity;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\Resolver\ProductPageItems;

/**
 * HTTP cache tags of hmDeals / hmBestSellers: hm_app_catalog (the cron purges
 * it at local midnight and when orders or the catalogue move) and the page's
 * products.
 */
class ProductPageIdentity implements IdentityInterface
{
    /**
     * @param array<mixed> $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        return array_values(array_unique(array_merge(
            [Tags::APP_CATALOG],
            Tags::products(array_map('intval', (array) ($resolvedData[ProductPageItems::IDS_KEY] ?? [])))
        )));
    }
}
