<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Model\Resolver\Identity;

use Magento\Framework\GraphQl\Query\Resolver\IdentityInterface;
use MagentoEgypt\HubAppBundle\Model\Pricing\PackagePricer;

/**
 * hmBundleQuote: the bundle and every product selected in the package, so a
 * price, stock or status change of any of them purges the cached quote.
 *
 * Core's product tags (Magento\Catalog\Model\Product::CACHE_TAG), generic tag
 * first as core's product identity does; spelt out here because this module
 * does not depend on MagentoEgypt_HubApp.
 */
class BundleQuoteIdentity implements IdentityInterface
{
    private const PRODUCT = 'cat_p';

    /**
     * @param array<mixed> $resolvedData
     * @return string[]
     */
    public function getIdentities(array $resolvedData): array
    {
        $tags = [self::PRODUCT];
        foreach ((array) ($resolvedData[PackagePricer::IDS_KEY] ?? []) as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $tags[] = self::PRODUCT . '_' . $id;
            }
        }

        return array_values(array_unique($tags));
    }
}
