<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Resolver\Identity;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\Resolver\BundleDeals;

/**
 * HTTP cache tags of hmBundleDeals: hm_app_catalog, hm_vendor (the cards name
 * their sellers) and the bundles on the page.
 */
class BundleDealIdentity implements IdentityInterface
{
    /**
     * @param array<mixed> $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        return array_values(array_unique(array_merge(
            [Tags::APP_CATALOG, Tags::VENDOR],
            Tags::products(array_map('intval', (array) ($resolvedData[BundleDeals::IDS_KEY] ?? [])))
        )));
    }
}
