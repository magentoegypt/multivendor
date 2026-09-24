<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Plugin;

use Magento\CatalogSearch\Model\Indexer\IndexerHandlerFactory;
use Magento\CatalogSearch\Model\Indexer\IndexSwitcherProxy;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Search\EngineResolverInterface;
use MagentoEgypt\AlgoliaVendor\Model\EngineOverride;
use MagentoEgypt\AlgoliaVendor\Model\OpenSearchMirrorIndexerHandler;
use Psr\Log\LoggerInterface;

/**
 * Keeps the OpenSearch catalog index current while Algolia is the engine.
 *
 * With catalog/search/engine = algolia, catalogsearch_fulltext resolves
 * Algolia's IndexerHandler, which does nothing — so OpenSearch silently froze
 * on the day of the switch (2026-09-22). That index is what the Algolia
 * fallback (SearchAdapterFallback) and the Luma storefront read.
 *
 * - aroundCreate: wrap Algolia's handler so every save/delete/clean is also
 *   applied to OpenSearch (Elasticsearch IndexerHandler, same $data).
 * - afterSwitchIndex: a FULL reindex writes a new versioned OpenSearch index
 *   and relies on the index switcher to move the alias; the proxy returns early
 *   for `algolia`, so repeat the switch as `opensearch`.
 */
class FulltextIndexerMirror
{
    public function __construct(
        private readonly EngineResolverInterface $engineResolver,
        private readonly ObjectManagerInterface $objectManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function aroundCreate(IndexerHandlerFactory $subject, callable $proceed, array $data = []): mixed
    {
        $handler = $proceed($data);

        if ($this->engineResolver->getCurrentSearchEngine() !== 'algolia') {
            return $handler;
        }

        try {
            $openSearch = EngineOverride::run('opensearch', function () use ($data) {
                return $this->createOpenSearchHandler($data);
            });
        } catch (\Throwable $e) {
            $this->logger->warning('OpenSearch mirror handler unavailable: ' . $e->getMessage());

            return $handler;
        }

        $om = $this->objectManager;
        $documentsFor = function (int $storeId, ?array $productIds) use ($om): \Traversable {
            // A fresh Full action with a fresh EngineProvider: both cache the
            // engine resource model at first use, so building them here, under
            // the `opensearch` override, gives OpenSearch-shaped documents.
            $full = $om->create(\Magento\CatalogSearch\Model\Indexer\Fulltext\Action\Full::class, [
                'engineProvider' => $om->create(\Magento\CatalogSearch\Model\ResourceModel\EngineProvider::class),
            ]);

            return new \ArrayIterator(iterator_to_array($full->rebuildStoreIndex($storeId, $productIds)));
        };
        $storeIdOf = static function ($dimensions): int {
            return (int) current($dimensions)->getValue();
        };

        return new OpenSearchMirrorIndexerHandler($handler, $openSearch, $this->logger, $documentsFor, $storeIdOf);
    }

    /**
     * A private OpenSearch indexing stack.
     *
     * Magento\Elasticsearch\Model\Config fixes its settings prefix from the
     * engine name at CONSTRUCTION and is shared; in an indexer process it has
     * already been built as `algolia`, so it looked for
     * catalog/search/algolia_server_hostname and the client refused to start
     * ("search engine misconfiguration", 2026-09-24). Build a Config with an
     * explicit `opensearch` prefix and hand it down the whole chain.
     */
    private function createOpenSearchHandler(array $data): mixed
    {
        $om = $this->objectManager;
        $config = $om->create(\Magento\Elasticsearch\Model\Config::class, ['prefix' => 'opensearch']);
        $connection = $om->create(
            \Magento\Elasticsearch\SearchAdapter\ConnectionManager::class,
            ['clientConfig' => $config]
        );
        $nameResolver = $om->create(
            \Magento\Elasticsearch\Model\Adapter\Index\IndexNameResolver::class,
            ['connectionManager' => $connection, 'clientConfig' => $config]
        );
        $adapter = $om->create(
            \Magento\Elasticsearch\Model\Adapter\Elasticsearch::class,
            ['connectionManager' => $connection, 'clientConfig' => $config, 'indexNameResolver' => $nameResolver]
        );
        $structure = $om->create(\Magento\Elasticsearch\Model\Indexer\IndexStructure::class, ['adapter' => $adapter]);

        return $om->create(
            \Magento\Elasticsearch\Model\Indexer\IndexerHandler::class,
            $data + ['adapter' => $adapter, 'indexNameResolver' => $nameResolver, 'indexStructure' => $structure]
        );
    }

    public function afterSwitchIndex(IndexSwitcherProxy $subject, mixed $result, array $dimensions): mixed
    {
        if (EngineOverride::current() === null && $this->engineResolver->getCurrentSearchEngine() === 'algolia') {
            try {
                EngineOverride::run('opensearch', fn() => $subject->switchIndex($dimensions));
            } catch (\Throwable $e) {
                $this->logger->warning('OpenSearch mirror switchIndex failed: ' . $e->getMessage());
            }
        }

        return $result;
    }
}
