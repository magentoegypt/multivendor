<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Store;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Psr\Log\LoggerInterface;

/**
 * A seller's reviews, page by page (hmStoreReviews).
 *
 * Which reviews: the ones the seller's rating counts, as SellerRatings (and the
 * website's VendorMeta) select them — approved (review.status_id = 1), on a
 * product of the seller (catalog_product_entity.vendor_id), that product
 * having its default-scope summary row (review_entity_summary.store_id = 0) —
 * narrowed to the ones the store view shows, as a product page lists them
 * (review_store, the Review collection's addStoreFilter()). A review is written
 * in one store view and shown there only, so an English review is not listed
 * on the Arabic store; the seller's rating still counts every approved one.
 *
 * Newest first (created_at, then id). Two queries per page — the count and
 * the rows — plus one for the page's votes.
 */
class SellerReviews
{
    public const MAX_PAGE_SIZE = 50;

    /** Magento\Review\Model\Review::STATUS_APPROVED, duplicated so this class loads without Magento_Review. */
    public const STATUS_APPROVED = 1;

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * One page of the seller's reviews shown in $storeId.
     *
     * @return array{total: int, rows: array<int, array{review_id: int, product_id: int, created_at: string,
     *               nickname: string, title: string, detail: string}>}
     */
    public function page(int $vendorId, int $storeId, int $pageSize, int $currentPage): array
    {
        $empty = ['total' => 0, 'rows' => []];
        if ($vendorId < 1 || $storeId < 1) {
            return $empty;
        }
        $pageSize = max(1, min(self::MAX_PAGE_SIZE, $pageSize));
        $currentPage = max(1, $currentPage);

        try {
            $connection = $this->resource->getConnection();
            $total = (int) $connection->fetchOne(
                $this->select($vendorId, $storeId)->columns(['total' => 'COUNT(DISTINCT r.review_id)'])
            );
            if ($total < 1 || ($currentPage - 1) * $pageSize >= $total) {
                return ['total' => $total, 'rows' => []];
            }

            $rows = $connection->fetchAll(
                $this->select($vendorId, $storeId)
                    ->join(
                        ['rd' => $this->resource->getTableName('review_detail')],
                        'rd.review_id = r.review_id',
                        ['nickname', 'title', 'detail']
                    )
                    ->columns(['review_id' => 'r.review_id', 'product_id' => 'r.entity_pk_value', 'created_at' => 'r.created_at'])
                    ->order(['r.created_at DESC', 'r.review_id DESC'])
                    ->limitPage($currentPage, $pageSize)
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: seller reviews unavailable: ' . $e->getMessage());

            return $empty;
        }

        $out = [];
        foreach ($rows as $row) {
            $id = (int) ($row['review_id'] ?? 0);
            //  One detail row per review; a second one (never written by core) is not a second review.
            if ($id < 1 || isset($out[$id])) {
                continue;
            }
            $out[$id] = [
                'review_id' => $id,
                'product_id' => (int) ($row['product_id'] ?? 0),
                'created_at' => (string) ($row['created_at'] ?? ''),
                'nickname' => trim((string) ($row['nickname'] ?? '')),
                'title' => trim((string) ($row['title'] ?? '')),
                'detail' => trim((string) ($row['detail'] ?? '')),
            ];
        }

        return ['total' => $total, 'rows' => array_values($out)];
    }

    /**
     * Each review's rating out of 5: the average of its votes (rating_option_vote.percent / 20), one decimal.
     *
     * @param int[] $reviewIds
     * @return array<int, float> review id => rating, for reviews that have votes
     */
    public function ratings(array $reviewIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $reviewIds), static fn (int $id): bool => $id > 0)));
        if (!$ids) {
            return [];
        }

        try {
            $connection = $this->resource->getConnection();
            $pairs = $connection->fetchPairs(
                $connection->select()
                    ->from(
                        $this->resource->getTableName('rating_option_vote'),
                        ['review_id', 'pct' => 'AVG(percent)']
                    )
                    ->where('review_id IN (?)', $ids)
                    ->group('review_id')
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: review votes unavailable: ' . $e->getMessage());

            return [];
        }

        $out = [];
        foreach ($pairs as $reviewId => $percent) {
            $percent = (float) $percent;
            if ($percent > 0) {
                $out[(int) $reviewId] = round($percent / 20, 1);
            }
        }

        return $out;
    }

    /**
     * The reviews the seller's rating counts (SellerRatings' joins and filters) that $storeId shows.
     */
    private function select(int $vendorId, int $storeId): Select
    {
        $connection = $this->resource->getConnection();
        $summary = $connection->select()
            ->from(['s' => $this->resource->getTableName('review_entity_summary')], [new \Zend_Db_Expr('1')])
            ->where('s.entity_pk_value = pe.entity_id')
            ->where('s.store_id = ?', 0);

        return $connection->select()
            ->from(['r' => $this->resource->getTableName('review')], [])
            ->join(['pe' => $this->resource->getTableName('catalog_product_entity')], 'pe.entity_id = r.entity_pk_value', [])
            ->join(['rs' => $this->resource->getTableName('review_store')], 'rs.review_id = r.review_id', [])
            ->where('r.status_id = ?', self::STATUS_APPROVED)
            ->where('pe.vendor_id = ?', $vendorId)
            ->where('rs.store_id = ?', $storeId)
            ->where('EXISTS (?)', $summary);
    }
}
