<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Product;

use Algolia\AlgoliaSearch\Service\AlgoliaConnector;
use Algolia\AlgoliaSearch\Service\IndexNameFetcher;
use Magento\Framework\App\ObjectManager;
use MagentoEgypt\HubApp\Api\ProductListLoaderInterface;
use Psr\Log\LoggerInterface;

/**
 * "Picked For You" for ONE shopper (hmPickedForYou), from Algolia Personalization.
 *
 * Home (hmAppHome) is a cached public request, so it can only carry the top-rated list. This asks Algolia,
 * per request, for the store's catalogue ranked twice in one call: with the shopper's userToken and
 * personalization on, and without. The profile is built from the Insights events the app (and the website,
 * DEV05) send under that token. If the two orders differ, the profile moved something and the shopper gets
 * that order (`personalized` true). If they match (no profile yet, or nothing in it applies), the
 * answer is the same top-rated list Home shows (`personalized` false), so the app never labels a generic list
 * as personal. Algolia trouble also falls back, logged, never thrown.
 *
 * Hits go through ProductListLoaderInterface::sellable() before anything is compared or counted, so only products a
 * shopper can buy here are ranked, compared and paged.
 *
 * Dependencies are optional with an ObjectManager fallback: the class was added without a di:compile, and
 * production's compiled DI builds unknown classes with no arguments.
 */
class PersonalPicks
{
    /** Longest list the query pages through (Refresh rotates 4 at a time). */
    public const MAX_PICKS = 16;

    /** Hits asked of Algolia: room for the sellable filter to drop some. */
    private const HITS = 48;

    /** The shape Algolia accepts for a userToken (same check as the website's server search). */
    private const TOKEN = '/^[A-Za-z0-9_=+\/.-]{1,129}$/';

    private readonly AlgoliaConnector $connector;
    private readonly IndexNameFetcher $indexNames;
    private readonly ProductListLoaderInterface $loader;
    private readonly RankedLists $lists;
    private readonly LoggerInterface $logger;

    public function __construct(
        ?AlgoliaConnector $connector = null,
        ?IndexNameFetcher $indexNames = null,
        ?ProductListLoaderInterface $loader = null,
        ?RankedLists $lists = null,
        ?LoggerInterface $logger = null
    ) {
        $om = ObjectManager::getInstance();
        $this->connector = $connector ?? $om->get(AlgoliaConnector::class);
        $this->indexNames = $indexNames ?? $om->get(IndexNameFetcher::class);
        $this->loader = $loader ?? $om->get(ProductListLoaderInterface::class);
        $this->lists = $lists ?? $om->get(RankedLists::class);
        $this->logger = $logger ?? $om->get(LoggerInterface::class);
    }

    public static function isValidToken(string $token): bool
    {
        return (bool) preg_match(self::TOKEN, $token);
    }

    /**
     * @return array{ids: int[], personalized: bool} up to MAX_PICKS sellable product ids, best first
     */
    public function forShopper(string $userToken, int $storeId): array
    {
        $fallback = fn (): array => array_slice(
            array_map('intval', $this->lists->topRated($storeId, self::MAX_PICKS)),
            0,
            self::MAX_PICKS
        );

        if (!self::isValidToken($userToken)) {
            return ['ids' => $fallback(), 'personalized' => false];
        }

        try {
            $index = $this->indexNames->getProductIndexName($storeId);
            $common = [
                'indexName' => $index,
                'query' => '',
                'hitsPerPage' => self::HITS,
                'attributesToRetrieve' => ['objectID'],
                'attributesToHighlight' => [],
                'analytics' => false,
                'clickAnalytics' => false,
            ];
            $response = $this->connector->getClient($storeId)->search(['requests' => [
                $common + ['userToken' => $userToken, 'enablePersonalization' => true],
                $common + ['enablePersonalization' => false],
            ]]);
            $results = is_array($response) ? ($response['results'] ?? []) : [];
            $personal = $this->loader->sellable(self::hitIds($results[0] ?? []), $storeId);
            $plain = $this->loader->sellable(self::hitIds($results[1] ?? []), $storeId);
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp hmPickedForYou: Algolia failed, top-rated used: ' . $e->getMessage());
            return ['ids' => $fallback(), 'personalized' => false];
        }

        return self::choose($personal, $plain, $fallback, self::MAX_PICKS);
    }

    /**
     * The decision, kept pure for the unit test: the personal order when personalization changed the first
     * $limit picks, otherwise the fallback.
     *
     * @param int[] $personal sellable ids, personalized order
     * @param int[] $plain sellable ids, the same search without personalization
     * @param callable(): int[] $fallback
     * @return array{ids: int[], personalized: bool}
     */
    public static function choose(array $personal, array $plain, callable $fallback, int $limit): array
    {
        $personal = array_slice(array_values(array_map('intval', $personal)), 0, $limit);
        $plain = array_slice(array_values(array_map('intval', $plain)), 0, $limit);

        if ($personal && $personal !== $plain) {
            return ['ids' => $personal, 'personalized' => true];
        }

        return ['ids' => $fallback(), 'personalized' => false];
    }

    /**
     * @param mixed $result one entry of a multi-search response
     * @return int[]
     */
    private static function hitIds(mixed $result): array
    {
        $ids = [];
        foreach ((array) ($result['hits'] ?? []) as $hit) {
            $id = (int) ($hit['objectID'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
