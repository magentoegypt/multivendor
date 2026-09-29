<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Seller;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * Star rating and review count per seller, as the website computes them.
 *
 * Same query as HomeSections VendorMeta::loadRatings() (the Featured Stores and
 * Top Vendors rails), restricted to the sellers asked for:
 *   - approved reviews only (review.status_id = 1);
 *   - the DEFAULT-scope summary only (review_entity_summary.store_id = 0): the
 *     per-store rows of the vendor-panel store hold 0 for every product and would
 *     drag a 4-5 star seller down to ~2;
 *   - rating_summary is a percentage, /20 gives stars out of 5, one decimal.
 *
 * One query per batch; results are kept for the rest of the request.
 */
class SellerRatings
{
    /** @var array<int, array{rating: float|null, review_count: int}> */
    private array $loaded = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param int[] $vendorIds
     * @return array<int, array{rating: float|null, review_count: int}> for every id asked (unrated: null, 0)
     */
    public function forVendors(array $vendorIds): array
    {
        $ids = [];
        foreach ($vendorIds as $vendorId) {
            $vendorId = (int) $vendorId;
            if ($vendorId > 0) {
                $ids[$vendorId] = $vendorId;
            }
        }

        $missing = array_values(array_diff_key($ids, $this->loaded));
        if ($missing) {
            foreach ($missing as $vendorId) {
                $this->loaded[$vendorId] = ['rating' => null, 'review_count' => 0];
            }
            $this->load($missing);
        }

        return array_intersect_key($this->loaded, $ids);
    }

    /**
     * @param int[] $vendorIds
     */
    private function load(array $vendorIds): void
    {
        try {
            $connection = $this->resource->getConnection();
            $select = $connection->select()
                ->from(['pe' => $this->resource->getTableName('catalog_product_entity')], ['vendor_id'])
                ->join(['r' => $this->resource->getTableName('review')], 'r.entity_pk_value = pe.entity_id', [])
                ->join(
                    ['s' => $this->resource->getTableName('review_entity_summary')],
                    's.entity_pk_value = pe.entity_id',
                    ['pct' => 'AVG(s.rating_summary)', 'total' => 'COUNT(DISTINCT r.review_id)']
                )
                ->where('r.status_id = ?', 1)
                ->where('s.store_id = ?', 0)
                ->where('pe.vendor_id IN (?)', $vendorIds)
                ->group('pe.vendor_id');

            foreach ($connection->fetchAll($select) as $row) {
                $percent = (float) $row['pct'];
                $this->loaded[(int) $row['vendor_id']] = [
                    //  Reviews without a single star vote average 0: that is "unrated", not "0 stars".
                    'rating' => $percent > 0 ? round($percent / 20, 1) : null,
                    'review_count' => (int) $row['total'],
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: seller ratings unavailable: ' . $e->getMessage());
        }
    }
}
