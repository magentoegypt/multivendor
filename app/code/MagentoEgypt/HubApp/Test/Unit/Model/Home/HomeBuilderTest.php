<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Home;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Api\Data\StoreInterface;
use MagentoEgypt\HubApp\Api\Home\SectionProviderInterface;
use MagentoEgypt\HubApp\Api\LinkResolverInterface;
use MagentoEgypt\HubApp\Api\ProductListLoaderInterface;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use MagentoEgypt\HubApp\Model\Cache\ResponseTtl;
use MagentoEgypt\HubApp\Model\Home\HomeBuilder;
use MagentoEgypt\HubApp\Model\Home\SectionProviderPool;
use MagentoEgypt\HubApp\Model\Home\SectionRepository;
use MagentoEgypt\HubApp\Model\Home\SectionResult;
use MagentoEgypt\HubApp\Model\Home\TitleResolver;
use MagentoEgypt\HubApp\Model\Resolver\Home\SectionProducts;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * A failed build is never cached as if it were the Home; the Home returned is
 * the one the storefront gate passes now.
 */
final class HomeBuilderTest extends TestCase
{
    private const ROWS = [
        ['section_id' => 1, 'type' => 'TRUST_ROW', 'is_active' => 1, 'store_id' => 0, 'audience' => 'all'],
        ['section_id' => 2, 'type' => 'TOP_BRANDS', 'is_active' => 1, 'store_id' => 0, 'audience' => 'all'],
    ];

    public function testAHomeWithEveryListedSectionIsCached(): void
    {
        $cache = $this->createMock(AppCache::class);
        $cache->method('load')->willReturn(null);
        $cache->expects(self::once())->method('save');
        $ttl = new ResponseTtl();

        $home = $this->builder(self::ROWS, $this->brands(), $cache, $ttl)->build($this->store(), null);

        self::assertSame([1, 2], array_column($home['sections'], 'id'));
        self::assertNull($ttl->getCap());
    }

    public function testASectionThatThrowsIsLeftOutAndTheBuildIsNotCached(): void
    {
        $failing = $this->createMock(SectionProviderInterface::class);
        $failing->method('provide')->willThrowException(new \RuntimeException('brands table gone'));
        $cache = $this->createMock(AppCache::class);
        $cache->method('load')->willReturn(null);
        $cache->expects(self::never())->method('save');
        $ttl = new ResponseTtl();

        $home = $this->builder(self::ROWS, $failing, $cache, $ttl)->build($this->store(), null);

        self::assertSame([1], array_column($home['sections'], 'id'));
        self::assertSame(HomeBuilder::DEGRADED_TTL, $ttl->getCap());
        self::assertContains('hm_app_home_2', $home[HomeBuilder::TAGS_KEY], 'saving the failed section purges this response');
        self::assertContains('hm_app_home_1', $home[HomeBuilder::TAGS_KEY]);
    }

    public function testUnreadableSectionRowsAreAnErrorNotAnEmptyHome(): void
    {
        $repository = $this->createMock(SectionRepository::class);
        $repository->method('getActiveRows')->willThrowException(new \RuntimeException('no such table'));
        $cache = $this->createMock(AppCache::class);
        $cache->method('load')->willReturn(null);
        $cache->expects(self::never())->method('save');

        $this->expectException(\RuntimeException::class);
        $this->builder([], $this->brands(), $cache, new ResponseTtl(), $repository)->build($this->store(), null);
    }

    public function testACachedHomeLosesWhatTheGateNoLongerPasses(): void
    {
        $cached = [
            'store_code' => 'en',
            'generated_at' => '2026-09-30T08:00:00Z',
            'sections' => [
                [
                    'id' => 5,
                    'type' => 'TODAYS_DEALS',
                    'countdown_ends_at' => '2026-10-01T23:59:59+03:00',
                    SectionProducts::IDS_KEY => [11, 12, 13],
                    SectionProducts::ENDS_KEY => ['11' => '2026-10-01 00:00:00', '12' => null, '13' => '2026-10-04 00:00:00'],
                ],
                ['id' => 6, 'type' => 'BEST_SELLERS', 'countdown_ends_at' => null, SectionProducts::IDS_KEY => [21, 22]],
                ['id' => 1, 'type' => 'TRUST_ROW', 'cms_block' => ['identifier' => 'hm_home_trust']],
            ],
            HomeBuilder::TAGS_KEY => ['hm_app_home', 'hm_app_catalog'],
        ];
        $cache = $this->createMock(AppCache::class);
        $cache->method('load')->willReturn($cached);
        $cache->expects(self::never())->method('save');
        //  11 sold out, and both best sellers.
        $loader = $this->createMock(ProductListLoaderInterface::class);
        $loader->method('sellable')->willReturnCallback(
            static fn (array $ids): array => array_values(array_diff($ids, [11, 21, 22]))
        );

        $home = $this->builder([], $this->brands(), $cache, new ResponseTtl(), null, $loader)->build($this->store(), null);

        self::assertSame([5, 1], array_column($home['sections'], 'id'), 'a product section with nothing left is omitted');
        self::assertSame([12, 13], $home['sections'][0][SectionProducts::IDS_KEY]);
        self::assertSame('2026-10-04T23:59:59+03:00', $home['sections'][0]['countdown_ends_at'], 'from the offers still shown');
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function builder(
        array $rows,
        SectionProviderInterface $brands,
        AppCache $cache,
        ResponseTtl $ttl,
        ?SectionRepository $repository = null,
        ?ProductListLoaderInterface $loader = null
    ): HomeBuilder {
        if ($loader === null) {
            $loader = $this->createMock(ProductListLoaderInterface::class);
            $loader->method('sellable')->willReturnArgument(0);
        }
        if ($repository === null) {
            $repository = $this->createMock(SectionRepository::class);
            $repository->method('getActiveRows')->willReturn($rows);
        }
        $trust = $this->createMock(SectionProviderInterface::class);
        $trust->method('provide')->willReturn(
            SectionResult::create()
                ->withField('cms_block', ['identifier' => 'hm_home_trust', 'title' => null, 'content' => '<p>Trust</p>'])
                ->withTags(['cms_b_hm_home_trust'])
        );
        $links = $this->createMock(LinkResolverInterface::class);
        $links->method('resolveMany')->willReturn([]);
        $emulation = $this->createMock(StorefrontEmulationInterface::class);
        $emulation->method('run')->willReturnCallback(static fn (int $storeId, callable $callback) => $callback());
        $config = $this->createMock(ScopeConfigInterface::class);
        $config->method('getValue')->willReturn('en_US');
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('getConfigTimezone')->willReturn('Asia/Riyadh');

        return new HomeBuilder(
            $repository,
            new SectionProviderPool(['TRUST_ROW' => $trust, 'TOP_BRANDS' => $brands]),
            new TitleResolver(),
            $links,
            $emulation,
            $loader,
            $cache,
            $ttl,
            $config,
            $timezone,
            $this->createMock(LoggerInterface::class)
        );
    }

    private function brands(): SectionProviderInterface
    {
        $brands = $this->createMock(SectionProviderInterface::class);
        $brands->method('provide')->willReturn(
            SectionResult::create()->withField('brands', [['id' => 4, 'name' => 'Brand']])->withTags(['hm_brand'])
        );

        return $brands;
    }

    private function store(): StoreInterface
    {
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(1);
        $store->method('getCode')->willReturn('en');
        $store->method('getWebsiteId')->willReturn(1);

        return $store;
    }
}
