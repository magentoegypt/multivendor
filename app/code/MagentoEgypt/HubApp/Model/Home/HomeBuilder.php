<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use MagentoEgypt\HomeSections\Model\Ranking\DealRanker;
use MagentoEgypt\HubApp\Api\LinkResolverInterface;
use MagentoEgypt\HubApp\Api\ProductListLoaderInterface;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use MagentoEgypt\HubApp\Model\Cache\ResponseTtl;
use MagentoEgypt\HubApp\Model\Cache\Tags;
use MagentoEgypt\HubApp\Model\Resolver\Home\SectionProducts;
use MagentoEgypt\HubApp\Model\Source\SectionType;
use Psr\Log\LoggerInterface;

/**
 * Builds the app Home (HmHome) of a store view and audience.
 *
 *   1. active rows of the store view, filtered by Schedule (dates, audience);
 *   2. inside ONE storefront emulation (theme translations, store locale):
 *      every "view all" link classified in one batch, then each section built
 *      by the provider registered for its type;
 *   3. a missing provider or a provider that throws drops that section with a
 *      log line; the rest of the Home still renders (house rule);
 *   4. empty results are omitted — nothing is invented; a placement
 *      (SectionType::PLACEMENT_TYPES, ACTIVE_ORDER) has no content and is sent
 *      as it is, the app drawing the viewer's own data there;
 *   5. the result is cached in the `hubapp` cache type per (store, audience)
 *      until the next schedule boundary, local midnight when deals are on it,
 *      or 15 minutes, whichever comes first.
 *
 * Failures are never cached as if they were the Home:
 *   - the section rows cannot be read: build() throws (SectionRepository),
 *     hmAppHome answers an error, and the app keeps its built-in Home;
 *   - a provider throws: its section is left out of THIS response only; the
 *     build is not saved in the app cache and the HTTP cache may keep the
 *     response for DEGRADED_TTL seconds at most (ResponseTtl), so the section
 *     is back as soon as its source is.
 *
 * Product sections carry their ranked, gated ids under SectionProducts::IDS_KEY;
 * the products themselves are loaded per request by that batch resolver, never
 * cached here. Cache tags of the content travel under TAGS_KEY for HomeIdentity.
 *
 * Every Home returned, cached or new, passes the storefront gate again
 * (shown()): a product that sold out or was disabled since the build leaves
 * its section, a product section left with nothing is omitted as the contract
 * says, and a deals countdown follows the offers still shown. The gate
 * remembers its answers for the request, so HmHomeSection.products and a
 * fresh build check nothing twice.
 */
class HomeBuilder
{
    /** Internal key of the built Home: cache tags of its content. */
    public const TAGS_KEY = '_tags';

    private const CACHE_PREFIX = 'home_';

    /** Longest the HTTP cache may keep a Home a section failed in, seconds. */
    public const DEGRADED_TTL = 120;

    /** Fields the builder owns; a provider cannot override them. */
    private const OWN_FIELDS = ['id', 'type', 'limit', 'personalizable'];

    public function __construct(
        private readonly SectionRepository $repository,
        private readonly SectionProviderPool $pool,
        private readonly TitleResolver $titles,
        private readonly LinkResolverInterface $links,
        private readonly StorefrontEmulationInterface $emulation,
        private readonly ProductListLoaderInterface $loader,
        private readonly AppCache $appCache,
        private readonly ResponseTtl $responseTtl,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly TimezoneInterface $timezone,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array<string, mixed> HmHome, plus TAGS_KEY
     */
    public function build(StoreInterface $store, ?string $audience): array
    {
        $storeId = (int) $store->getId();
        $audience = Schedule::normaliseAudience($audience);
        $cacheKey = self::CACHE_PREFIX . $storeId . '_' . strtolower($audience);
        $timezone = (string) $this->timezone->getConfigTimezone(ScopeInterface::SCOPE_STORE, $storeId) ?: 'UTC';

        $cached = $this->appCache->load($cacheKey);
        if ($cached !== null && isset($cached['sections'])) {
            return $this->shown($cached, $storeId, $timezone);
        }

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $rows = $this->repository->getActiveRows($storeId);
        $visible = array_values(array_filter(
            $rows,
            static fn (array $row): bool => SectionType::isKnown((string) ($row['type'] ?? ''))
                && Schedule::isVisible($row, $storeId, $audience, $now)
        ));

        $locale = (string) $this->scopeConfig->getValue('general/locale/code', ScopeInterface::SCOPE_STORE, $storeId);

        $built = $this->emulation->run(
            $storeId,
            fn (): array => $this->buildSections($visible, $store, $audience, $locale, $timezone, $now)
        );

        $home = [
            'store_code' => (string) $store->getCode(),
            'generated_at' => $now->format('Y-m-d\TH:i:s\Z'),
            'sections' => $built['sections'],
            self::TAGS_KEY => $built['tags'],
        ];

        if ($built['degraded']) {
            $this->responseTtl->cap(self::DEGRADED_TTL);
        } else {
            $this->appCache->save($cacheKey, $home, $built['tags'], $this->ttl($rows, $now, $timezone, $built['daily']));
        }

        return $this->shown($home, $storeId, $timezone);
    }

    /**
     * The Home as the storefront gate passes it now (see the class note).
     *
     * @param array<string, mixed> $home
     * @return array<string, mixed>
     */
    private function shown(array $home, int $storeId, string $timezone): array
    {
        $sections = [];
        foreach ((array) $home['sections'] as $section) {
            if (is_array($section) && array_key_exists(SectionProducts::IDS_KEY, $section)) {
                $ids = $this->loader->sellable(array_map('intval', (array) $section[SectionProducts::IDS_KEY]), $storeId);
                if (!$ids && in_array((string) ($section['type'] ?? ''), SectionType::PRODUCT_TYPES, true)) {
                    continue;
                }
                $section[SectionProducts::IDS_KEY] = $ids;
                if (isset($section[SectionProducts::ENDS_KEY]) && is_array($section[SectionProducts::ENDS_KEY])) {
                    $ends = $section[SectionProducts::ENDS_KEY];
                    $section['countdown_ends_at'] = DealRanker::soonestEnd(
                        array_map(static fn (int $id): ?string => isset($ends[$id]) ? (string) $ends[$id] : null, $ids),
                        $timezone
                    );
                }
            }
            $sections[] = $section;
        }
        $home['sections'] = $sections;

        return $home;
    }

    /**
     * @param array<int, array<string, mixed>> $rows visible rows, admin order
     * @return array{sections: array<int, array<string, mixed>>, tags: string[], daily: bool, degraded: bool}
     */
    private function buildSections(
        array $rows,
        StoreInterface $store,
        string $audience,
        string $locale,
        string $timezone,
        \DateTimeImmutable $now
    ): array {
        $storeId = (int) $store->getId();
        $arabic = TitleResolver::isArabic($locale);

        $targets = [];
        foreach ($rows as $row) {
            $targets[(int) $row['section_id']] = $row['more_url'] ?? null;
        }
        $moreLinks = $targets ? $this->links->resolveMany($targets, $storeId) : [];

        $sections = [];
        $tags = [Tags::APP_HOME];
        $daily = false;
        $degraded = false;

        foreach ($rows as $row) {
            $id = (int) $row['section_id'];
            $type = strtoupper((string) $row['type']);

            $provider = $this->pool->get($type);
            if ($provider === null) {
                //  The satellite that builds this type is not installed (or disabled).
                $this->logger->info(sprintf('HubApp: Home section %d (%s) has no provider; omitted.', $id, $type));
                continue;
            }

            $context = new SectionContext(
                $row,
                $storeId,
                (string) $store->getCode(),
                (int) $store->getWebsiteId(),
                $locale,
                $audience,
                $now,
                $timezone
            );

            try {
                $result = $provider->provide($context);
            } catch (\Throwable $e) {
                $this->logger->error(sprintf(
                    'HubApp: Home section %d (%s) failed and was omitted: %s',
                    $id,
                    $type,
                    $e->getMessage()
                ), ['exception' => $e]);
                //  Not cached (see build()); saving the section still purges this response.
                $degraded = true;
                $tags[] = Tags::homeSection($id);
                continue;
            }
            if ($result === null || $result->isEmpty()) {
                continue;
            }

            $section = [
                'id' => $id,
                'type' => $type,
                'title' => $this->titles->title($row, $arabic, $result->getDefaultTitle()),
                'subtitle' => $this->titles->subtitle($row, $arabic),
                'limit' => $context->getLimit(),
                'ends_at' => Schedule::isoUtc($row['ends_at'] ?? null),
                'countdown_ends_at' => null,
                'personalizable' => $type === SectionType::PICKED_FOR_YOU,
                'more_link' => $moreLinks[$id] ?? $result->getDefaultMoreLink(),
            ];
            foreach ($result->getFields() as $name => $value) {
                if (!in_array($name, self::OWN_FIELDS, true)) {
                    $section[$name] = $value;
                }
            }
            if ($result->getProductIds() || in_array($type, SectionType::PRODUCT_TYPES, true)) {
                $section[SectionProducts::IDS_KEY] = $result->getProductIds();
            }

            $tags = array_merge($tags, $result->getTags(), [Tags::homeSection($id)]);
            $daily = $daily || $type === SectionType::TODAYS_DEALS;
            $sections[] = $section;
        }

        return [
            'sections' => $sections,
            'tags' => array_values(array_unique($tags)),
            'daily' => $daily,
            'degraded' => $degraded,
        ];
    }

    /**
     * Seconds the built Home stays valid: the next schedule boundary, local
     * midnight when it shows deals, at most AppCache::MAX_TTL.
     *
     * @param array<int, array<string, mixed>> $rows all active rows (future ones too)
     */
    private function ttl(array $rows, \DateTimeImmutable $now, string $timezone, bool $daily): int
    {
        $ttl = AppCache::MAX_TTL;

        $next = Schedule::nextBoundary($rows, $now);
        if ($next !== null) {
            $ttl = min($ttl, $next->getTimestamp() - $now->getTimestamp());
        }
        if ($daily) {
            try {
                $midnight = new \DateTimeImmutable('tomorrow', new \DateTimeZone($timezone));
                $ttl = min($ttl, $midnight->getTimestamp() - $now->getTimestamp());
            } catch (\Throwable $e) {
                $ttl = min($ttl, 300);
            }
        }

        return max(1, $ttl);
    }
}
