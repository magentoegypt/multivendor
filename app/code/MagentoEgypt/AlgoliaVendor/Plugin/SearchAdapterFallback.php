<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Plugin;

use Algolia\AlgoliaSearch\Exceptions\RetriableException;
use Algolia\AlgoliaSearch\Exceptions\UnreachableException;
use Algolia\SearchAdapter\Model\Adapter;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Search\RequestInterface;
use MagentoEgypt\AlgoliaVendor\Model\EngineOverride;
use Psr\Log\LoggerInterface;

/**
 * DEV04 requirement: "fall back to default Magento search if Algolia is down".
 *
 * Algolia's SearchAdapter has no fallback — Adapter::query() lets every
 * AlgoliaException through and the category or results page answers 500. Here
 * the same Magento search request is answered by Magento's own OpenSearch
 * adapter instead (the index is kept current by FulltextIndexerMirror), so the
 * page renders with the same templates, filters and paging.
 *
 * When Algolia is UNREACHABLE (network/timeouts, not a bad request) a 60 s
 * breaker skips it entirely, so an outage does not add a connection timeout to
 * every page view.
 */
class SearchAdapterFallback
{
    private const BREAKER = 'hm_algolia_search_unreachable';
    private const BREAKER_SECONDS = 60;

    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger
    ) {
    }

    public function aroundQuery(Adapter $subject, callable $proceed, RequestInterface $request): mixed
    {
        if ($this->cache->load(self::BREAKER)) {
            return $this->openSearch($request);
        }

        try {
            return $proceed($request);
        } catch (\Throwable $e) {
            $unreachable = $e instanceof UnreachableException || $e instanceof RetriableException;

            if ($unreachable) {
                $this->cache->save('1', self::BREAKER, [], self::BREAKER_SECONDS);
            }

            $this->logger->warning(sprintf(
                'Algolia search failed (%s: %s); served "%s" from OpenSearch%s.',
                get_class($e),
                $e->getMessage(),
                $request->getName(),
                $unreachable ? ' and skipping Algolia for ' . self::BREAKER_SECONDS . 's' : ''
            ));

            return $this->openSearch($request);
        }
    }

    private function openSearch(RequestInterface $request): mixed
    {
        return EngineOverride::run('opensearch', function () use ($request) {
            return $this->objectManager->get(\Magento\OpenSearch\SearchAdapter\Adapter::class)->query($request);
        });
    }
}
