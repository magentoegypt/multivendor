<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home\Provider;

use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\HubApp\Api\Home\SectionProviderInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\Catalog\BundleCards;
use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Home\SectionResult;

/**
 * BUNDLE_DEALS: the newest bundle cards, the website's bundle rail rules
 * (BundleCards / HomeSections BundleDealBuilder), optionally only those under
 * the section's top-level category.
 */
class BundleDealsProvider implements SectionProviderInterface
{
    public function __construct(
        private readonly BundleCards $bundleCards,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function provide(SectionContext $context): ?SectionResult
    {
        $storeId = $context->getStoreId();
        $cards = $this->bundleCards->get($storeId);
        $items = array_slice(
            BundleCards::inCategory($cards['items'], $context->getCategoryId()),
            0,
            $context->getLimit()
        );
        if (!$items) {
            return null;
        }

        return SectionResult::create()
            ->withField('bundles', $this->bundleCards->toGraphQl($items, $this->storeManager->getStore($storeId)))
            //  The bundles' own product tags purge the Home from the HTTP cache when
            //  one of them is edited; the app cache ignores product tags (see Tags).
            //  hm_vendor: the cards name their sellers.
            ->withTags(array_merge([Tags::APP_CATALOG, Tags::VENDOR], Tags::products(BundleCards::ids($items))));
    }
}
