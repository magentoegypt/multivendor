<?php
/**
 * Keep unapproved vendor products out of the search index.
 *
 * WHY THE INDEX AND NOT THE PAGE
 * ------------------------------
 * Category listings on this build are fulltext collections: OpenSearch decides
 * WHICH PAGE a product falls on and how many pages there are, and only then
 * does the SQL add Vnecoms' `approval = 2`. The two disagreed on 51 products,
 * so every page holding one of them rendered short — /all.html page 1 showed
 * five cards in a twelve-card page and the pager still promised six pages
 * ([CL036-DEV01.21], "the full products page doesn't show completely").
 *
 * Filtering the page after the fact cannot fix that: the count and the page
 * boundaries are already wrong by then. The set the engine pages over has to be
 * the set the storefront will serve, which means the products must not be in
 * the index at all.
 *
 * WHERE IT HOOKS AND WHY HERE
 * ---------------------------
 * saveIndex() is the last point where the documents are still a stream we can
 * narrow, and narrowing it has no other consequence: the caller has already
 * decided which products to rebuild, cleanIndex()/deleteIndex() have already
 * run, and updateAlias() still happens.
 *
 * The obvious-looking alternative — filtering
 * Fulltext\Action\DataProvider::getSearchableProducts() — is a trap. Full
 * indexing walks the catalog in batches of 100 and stops on the first EMPTY
 * batch; a run of 100 consecutive unapproved products would therefore silently
 * truncate the index at that point and take the rest of the catalog with it.
 *
 * SELF-HEALING. Approving a product saves it, which schedules it in mview,
 * which reindexes it — deleteIndex() then saveIndex(), and this time the gate
 * passes it. Nothing here has to be re-run by hand.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\Indexer;

use Magento\Elasticsearch\Model\Indexer\IndexerHandler;
use MagentoEgypt\VendorExtend\Model\StorefrontVisibility;

class ExcludeUnapprovedProducts
{
    /**
     * Ids per gate query. The documents arrive as a generator that is building
     * each one as it goes, so buffering is not free — this is a compromise
     * between round trips and holding rows in memory. The indexer's own save
     * batch is 500.
     */
    private const CHUNK = 500;

    public function __construct(private readonly StorefrontVisibility $visibility)
    {
    }

    /**
     * @param IndexerHandler $subject
     * @param array $dimensions
     * @param \Traversable $documents
     * @return array
     */
    public function beforeSaveIndex(IndexerHandler $subject, $dimensions, \Traversable $documents): array
    {
        return [$dimensions, $this->approvedOnly($documents)];
    }

    /**
     * The same stream with the unapproved documents left out.
     *
     * Keys are product ids and every consumer downstream reads them
     * (Batch::getItems keys its batches by them), so they are preserved.
     */
    private function approvedOnly(\Traversable $documents): \Generator
    {
        $buffer = [];

        foreach ($documents as $productId => $document) {
            $buffer[$productId] = $document;

            if (count($buffer) >= self::CHUNK) {
                yield from $this->passing($buffer);
                $buffer = [];
            }
        }

        if ($buffer) {
            yield from $this->passing($buffer);
        }
    }

    /**
     * @param array<int, mixed> $buffer
     * @return array<int, mixed>
     */
    private function passing(array $buffer): array
    {
        $allowed = array_flip($this->visibility->approvedIds(array_keys($buffer)));

        return array_intersect_key($buffer, $allowed);
    }
}
