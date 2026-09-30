<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Home\Provider;

use MagentoEgypt\HubApp\Api\LinkResolverInterface;
use MagentoEgypt\HubApp\Model\Home\SectionContext;
use MagentoEgypt\HubAppVendors\Model\Home\Provider\StoresProvider;
use MagentoEgypt\HubAppVendors\Model\Store\StoreListQuery;
use MagentoEgypt\HubAppVendors\Model\Store\StoreSorter;
use PHPUnit\Framework\TestCase;

/**
 * The admin's "Sellers" choice: FEATURED_STORES shows them in admin order, TOP_VENDORS ranks only them.
 * Constructor arguments as in etc/graphql/di.xml.
 */
final class StoresProviderTest extends TestCase
{
    public function testTopVendorsRanksOnlyTheChosenSellers(): void
    {
        $provider = $this->provider(['codes' => ['ENARA', 'loly']], StoreSorter::TOP_RATED, StoreSorter::TOP_RATED, true);

        self::assertNotNull($provider->provide($this->context('TOP_VENDORS', 'ENARA,loly')));
    }

    public function testTopVendorsWithoutAChoiceRanksEverySeller(): void
    {
        $provider = $this->provider([], StoreSorter::TOP_RATED, StoreSorter::TOP_RATED, true);

        self::assertNotNull($provider->provide($this->context('TOP_VENDORS', null)));
    }

    public function testTopVendorsKeepsTheSortTheSectionSets(): void
    {
        $provider = $this->provider(['codes' => ['ENARA']], StoreSorter::NEWEST, StoreSorter::TOP_RATED, true);

        self::assertNotNull($provider->provide($this->context('TOP_VENDORS', 'ENARA', 'NEWEST')));
    }

    public function testFeaturedStoresKeepTheAdminOrder(): void
    {
        $provider = $this->provider(['codes' => ['MIA', 'ENARA']], null, StoreSorter::FEATURED, true, true, true);

        self::assertNotNull($provider->provide($this->context('FEATURED_STORES', 'MIA,ENARA')));
    }

    public function testFeaturedStoresWithoutAChoiceFallBackToShowOnHome(): void
    {
        $provider = $this->provider(['featured' => true], StoreSorter::FEATURED, StoreSorter::FEATURED, true, true, true);

        self::assertNotNull($provider->provide($this->context('FEATURED_STORES', '')));
    }

    /**
     * The seeded Featured Stores name this store's sellers; where none of them exists (or is approved),
     * the section is left out of the Home instead of failing.
     */
    public function testFeaturedStoresWithNoneOfTheirSellersLeftAreLeftOut(): void
    {
        $storeList = $this->createMock(StoreListQuery::class);
        $storeList->expects(self::once())->method('execute')
            ->with(['codes' => ['ENARA', 'ronza', 'loly', 'MIA']], null, 8, 1, 1)
            ->willReturn([
                'items' => [],
                'total_count' => 0,
                'page_info' => ['page_size' => 8, 'current_page' => 1, 'total_pages' => 0],
            ]);
        $provider = new StoresProvider(
            $storeList,
            $this->createMock(LinkResolverInterface::class),
            StoreSorter::FEATURED,
            true,
            true,
            true
        );

        self::assertNull($provider->provide($this->context('FEATURED_STORES', 'ENARA,ronza,loly,MIA')));
    }

    public function testNewStoresIgnoreSellerCodes(): void
    {
        $provider = $this->provider([], StoreSorter::NEWEST, StoreSorter::NEWEST);

        self::assertNotNull($provider->provide($this->context('NEW_STORES', 'ENARA')));
    }

    /**
     * @param array<string, mixed> $expectedFilter
     */
    private function provider(
        array $expectedFilter,
        ?string $expectedSort,
        string $defaultSort,
        bool $chosenSellers = false,
        bool $featuredFallback = false,
        bool $keepChosenOrder = false
    ): StoresProvider {
        $storeList = $this->createMock(StoreListQuery::class);
        $storeList->expects(self::once())->method('execute')
            ->with($expectedFilter, $expectedSort, 8, 1, 1)
            ->willReturn([
                'items' => [['vendor_entity_id' => 7, 'code' => 'ENARA']],
                'total_count' => 1,
                'page_info' => ['page_size' => 8, 'current_page' => 1, 'total_pages' => 1],
            ]);
        $links = $this->createMock(LinkResolverInterface::class);
        $links->method('resolve')->willReturn(null);

        return new StoresProvider($storeList, $links, $defaultSort, $chosenSellers, $featuredFallback, $keepChosenOrder);
    }

    private function context(string $type, ?string $codes, ?string $sort = null): SectionContext
    {
        return new SectionContext(
            ['section_id' => 3, 'type' => $type, 'vendor_codes' => $codes, 'sort_by' => $sort, 'item_limit' => 8],
            1,
            'en',
            1,
            'en_US',
            'GUEST',
            new \DateTimeImmutable('2026-09-30 10:00:00', new \DateTimeZone('UTC')),
            'Asia/Riyadh'
        );
    }
}
