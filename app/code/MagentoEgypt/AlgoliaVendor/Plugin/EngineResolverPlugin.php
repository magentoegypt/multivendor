<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Plugin;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\State;
use Magento\Search\Model\EngineResolver;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\AlgoliaVendor\Model\EngineOverride;

/**
 * Makes the search engine effectively per store, and lets the Algolia fallback
 * borrow OpenSearch for one call.
 *
 * catalog/search/engine is read at DEFAULT scope only, so switching it to
 * `algolia` for Hub Market also switched the Luma storefront — whose Algolia
 * indices were deliberately never built (enable_indexing=0 on luma_en/luma_ar/
 * vendors). Luma search then answered HTTP 500 "Index hubmarket_luma_en_products
 * does not exist" (2026-09-24). Here, on the storefront, any store that does not
 * index into Algolia keeps using OpenSearch, which Magento still maintains for
 * it (see FulltextIndexerMirror).
 */
class EngineResolverPlugin
{
    private const ALGOLIA = 'algolia';
    private const OPENSEARCH = 'opensearch';
    private const XML_ENABLE_INDEXING = 'algoliasearch_indexing_manager/algolia_indexing/enable_indexing';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly State $appState
    ) {
    }

    public function afterGetCurrentSearchEngine(EngineResolver $subject, mixed $result): mixed
    {
        $forced = EngineOverride::current();

        if ($forced !== null) {
            return $forced;
        }

        if ($result !== self::ALGOLIA) {
            return $result;
        }

        /*
         * Command line (cron's indexer:reindex / mview, bin/magento): Magento's
         * catalogsearch_fulltext indexer is the only engine consumer there, and
         * under `algolia` it indexes into a no-op handler with an engine that
         * allows no visibility — OpenSearch froze on 2026-09-22. As
         * `opensearch` it maintains the OpenSearch index natively, which the
         * Algolia fallback and Luma read. Algolia's own indexing does not use
         * catalog/search/engine. (Plugin\FulltextIndexerMirror then stays
         * dormant: it only engages when the engine resolves to `algolia`.)
         */
        if (PHP_SAPI === 'cli') {
            return self::OPENSEARCH;
        }

        try {
            $area = $this->appState->getAreaCode();
            /*
             * Storefront GraphQL (`products(filter:…)`) runs Magento's search request through
             * the engine's adapter, and Algolia's SearchAdapter does not apply GraphQL filters:
             * every filter-only query returned the whole catalogue (315 items), whatever the
             * filter (2026-09-26). GraphQL is not what Algolia's storefront features run on,
             * so it is answered by OpenSearch, which Magento keeps current for exactly this.
             */
            if ($area === Area::AREA_GRAPHQL) {
                return self::OPENSEARCH;
            }
            if ($area !== Area::AREA_FRONTEND) {
                return $result;
            }

            $storeId = (int) $this->storeManager->getStore()->getId();

            if (!$this->scopeConfig->isSetFlag(self::XML_ENABLE_INDEXING, ScopeInterface::SCOPE_STORE, $storeId)) {
                return self::OPENSEARCH;
            }
        } catch (\Throwable $e) {
            // Area or store not initialised yet (bootstrap, setup): keep the configured engine.
        }

        return $result;
    }
}
