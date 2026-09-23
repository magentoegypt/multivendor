<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Plugin;

use Algolia\AlgoliaSearch\Api\Data\IndexOptionsInterface;
use Algolia\AlgoliaSearch\Helper\Entity\SuggestionHelper;
use Algolia\AlgoliaSearch\Service\AlgoliaConnector;
use Psr\Log\LoggerInterface;

/**
 * Stops an empty temporary index being promoted over a populated one.
 *
 * THE UPSTREAM BUG THIS WORKS AROUND (algoliasearch-magento-2 3.18.1)
 * ------------------------------------------------------------------
 * Service/Suggestion/IndexBuilder builds the suggestion records correctly and
 * then throws them away. The two halves disagree about where they are working:
 *
 *   rebuildStoreSuggestionIndexPage() line 114
 *     $indexOptions = $this->indexOptionsBuilder->buildEntityIndexOptions($storeId);
 *     ... saveObjects($indexData, $indexOptions)      <- writes to the LIVE index
 *
 *   moveStoreSuggestionIndex()        lines 148-150
 *     $tmpIndexOptions = ...buildEntityIndexOptions($storeId, true);
 *     moveIndex($tmpIndexOptions, $indexOptions)      <- moves the EMPTY _tmp
 *                                                        index ON TOP of it
 *
 * The second argument is the "is temporary" flag, and the save omits it. So the
 * records land in `<prefix>_<locale>_suggestions`, and the move then renames a
 * `_suggestions_tmp` index that was never written over the top, leaving zero
 * records every time. Service/Page/IndexBuilder (line 83) passes the flag and is
 * unaffected — which is why pages index fine and suggestions never can.
 *
 * Symptom, if this is ever removed: the suggestions index sits at 0 records
 * while `algolia:reindex:suggestions` and the queue both report success, and
 * nothing appears in `algoliasearch_queue_archive` because nothing threw. The
 * index's updatedAt still moves, because the destructive move itself counts as
 * a write. Do not go looking at min_popularity — verify the collection size with
 * SuggestionHelper::getSuggestionCollectionQuery($storeId)->getSize() first.
 *
 * WHY THE GUARD IS PHRASED THIS WAY
 * ---------------------------------
 * It refuses the move only when the SOURCE is a temporary index that holds
 * nothing, so it is safe in both directions: today it protects the records the
 * buggy builder just wrote, and if Algolia fixes the builder to write to `_tmp`
 * the source will be populated, the guard will not match, and the move proceeds
 * exactly as upstream intends. Nothing has to be unwound at upgrade time.
 *
 * Scoped to suggestions on purpose. Products, categories and pages use the same
 * moveIndex() correctly, and deliberately emptying one of those is a legitimate
 * thing for a merchant to do — this must not quietly block it.
 */
class PreserveSuggestionIndex
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    /**
     * @param AlgoliaConnector      $subject
     * @param callable              $proceed
     * @param IndexOptionsInterface $fromIndexOptions
     * @param IndexOptionsInterface $toIndexOptions
     */
    public function aroundMoveIndex(
        AlgoliaConnector $subject,
        callable $proceed,
        IndexOptionsInterface $fromIndexOptions,
        IndexOptionsInterface $toIndexOptions
    ): void {
        if ($this->wouldDestroy($subject, $fromIndexOptions, $toIndexOptions)) {
            $this->logger->info(sprintf(
                'MagentoEgypt_AlgoliaVendor: skipped moving the empty %s over %s '
                . '(algoliasearch-magento-2 writes suggestion records to the live index, not the temporary one).',
                (string) $fromIndexOptions->getIndexName(),
                (string) $toIndexOptions->getIndexName()
            ));

            //  The move would have CONSUMED the temporary index (a move is a
            //  rename). Skipping it leaves `_suggestions_tmp` behind, because
            //  moveStoreSuggestionIndex() calls copyQueryRules(live -> tmp) just
            //  before moveIndex(), which creates it. On the Free plan's hard
            //  20-index cap that is two slots per store pair lost for nothing
            //  (measured: 15 -> 17 after one run). It has just been measured as
            //  holding zero records, so removing it loses nothing — it is exactly
            //  the state the upstream move would have left.
            try {
                $subject->deleteIndex($fromIndexOptions);
            } catch (\Throwable $e) {
                $this->logger->warning(
                    'MagentoEgypt_AlgoliaVendor: could not remove ' . (string) $fromIndexOptions->getIndexName()
                    . ': ' . $e->getMessage()
                );
            }

            return;
        }

        $proceed($fromIndexOptions, $toIndexOptions);
    }

    private function wouldDestroy(
        AlgoliaConnector $subject,
        IndexOptionsInterface $from,
        IndexOptionsInterface $to
    ): bool {
        if (!$from->isTemporaryIndex() || $to->getIndexSuffix() !== SuggestionHelper::INDEX_NAME_SUFFIX) {
            return false;
        }

        $name = (string) $from->getIndexName();

        if ($name === '') {
            return false;
        }

        //  FAIL OPEN. If the count cannot be established the move goes ahead —
        //  this plugin must never be the reason an index stops being rebuilt.
        try {
            return $this->recordCount($subject, $name, $from->getStoreId()) === 0;
        } catch (\Throwable $e) {
            $this->logger->warning(
                'MagentoEgypt_AlgoliaVendor: could not size ' . $name . ', allowing the move: ' . $e->getMessage()
            );

            return false;
        }
    }

    /**
     * Records in $indexName, or 0 when Algolia has never heard of it.
     *
     * listIndexes() rather than indexExists(): an index that exists but is empty
     * is just as destructive to promote as one that does not exist, and the
     * listing answers both questions in the one request that is already cheap.
     */
    private function recordCount(AlgoliaConnector $subject, string $indexName, ?int $storeId): int
    {
        $response = $subject->listIndexes($storeId);
        $items = (is_array($response) ? $response : (array) $response)['items'] ?? [];

        foreach ($items as $item) {
            $item = (array) $item;

            if (($item['name'] ?? null) === $indexName) {
                return (int) ($item['entries'] ?? 0);
            }
        }

        return 0;
    }
}
