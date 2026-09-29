<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Home\Provider;

use MagentoEgypt\HubApp\Api\Home\SectionProviderInterface;
use MagentoEgypt\HubApp\Api\LinkResolverInterface;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubApp\Model\Home\SectionResult;
use MagentoEgypt\HubAppVendors\Model\Store\StoreListQuery;
use MagentoEgypt\HubAppVendors\Model\Store\StoreSorter;

/**
 * Seller-card Home sections: FEATURED_STORES, TOP_VENDORS and NEW_STORES (HmHomeSection.stores).
 *
 * One class, three configurations (etc/graphql/di.xml virtual types):
 *
 *   FEATURED_STORES  the sellers chosen in the section (vendor_codes), in that
 *                    order; with none chosen, the sellers flagged "Show on Home"
 *                    (is_home) — the website's Seller List widget does the same;
 *   TOP_VENDORS      highest rated first (the website's "Top Vendors" rail);
 *   NEW_STORES       newest first (the website's "New Stores" rail).
 *
 * The section's sort_by overrides the order when it is an HmStoreSort value.
 * Only approved sellers with listable products are shown; nothing to show
 * returns null and the builder omits the section. No per-viewer data: the
 * built Home is shared by every viewer of the store view.
 */
class StoresProvider implements SectionProviderInterface
{
    /**
     * @param string $defaultSort HmStoreSort value used when the section sets none
     * @param bool $chosenSellers true: honour the section's vendor_codes (FEATURED_STORES)
     * @param bool $featuredFallback true: without chosen sellers, only sellers flagged "Show on Home"
     */
    public function __construct(
        private readonly StoreListQuery $storeList,
        private readonly LinkResolverInterface $linkResolver,
        private readonly string $defaultSort = StoreSorter::FEATURED,
        private readonly bool $chosenSellers = false,
        private readonly bool $featuredFallback = false
    ) {
    }

    /**
     * @inheritDoc
     */
    public function provide(SectionContext $context): ?SectionResult
    {
        $storeId = $context->getStoreId();
        $configuredSort = StoreSorter::normalise($context->getSortBy());

        $filter = [];
        $sort = $configuredSort ?? StoreSorter::normalise($this->defaultSort) ?? StoreSorter::DEFAULT_SORT;
        $codes = $this->chosenSellers ? $context->getVendorCodes() : [];
        if ($codes) {
            $filter['codes'] = $codes;
            //  Chosen sellers keep the admin's order unless the section asks for another.
            $sort = $configuredSort;
        } elseif ($this->featuredFallback) {
            $filter['featured'] = true;
        }

        $page = $this->storeList->execute($filter, $sort, $context->getLimit(), 1, $storeId);
        if (!$page['items']) {
            return null;
        }

        $tags = [Tags::VENDOR];
        foreach ($page['items'] as $card) {
            $tags[] = Tags::vendor((int) $card['vendor_entity_id']);
        }

        return SectionResult::create()
            ->withField('stores', $page['items'])
            ->withTags($tags)
            ->withDefaultMoreLink($this->linkResolver->resolve('sellerlist', $storeId));
    }
}
