<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Seller;

use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use MagentoEgypt\HubApp\Model\Cache\Tags;
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
 * The ratings of EVERY seller are computed in one query and kept in the
 * `hubapp` app cache (up to AppCache::MAX_TTL, tagged hm_vendor, so saving a
 * seller or a review of a seller's product purges them): the query joins the
 * review tables and would otherwise run for every "sold by" of every product
 * and cart response on a slow server. The table holds a few dozen sellers.
 */
class SellerRatings
{
    private const CACHE_KEY = 'seller_ratings';

    /** @var array<int, array{rating: float|null, review_count: int}>|null every rated seller */
    private ?array $loaded = null;

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly AppCache $appCache,
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

        if ($this->loaded === null) {
            $this->loaded = $this->loadAll();
        }

        $out = [];
        foreach ($ids as $vendorId) {
            $out[$vendorId] = $this->loaded[$vendorId] ?? ['rating' => null, 'review_count' => 0];
        }

        return $out;
    }

    /**
     * @return array<int, array{rating: float|null, review_count: int}>
     */
    private function loadAll(): array
    {
        $cached = $this->appCache->load(self::CACHE_KEY);
        if ($cached !== null) {
            $out = [];
            foreach ($cached as $vendorId => $row) {
                if (is_array($row)) {
                    $out[(int) $vendorId] = [
                        'rating' => isset($row['rating']) ? (float) $row['rating'] : null,
                        'review_count' => (int) ($row['review_count'] ?? 0),
                    ];
                }
            }

            return $out;
        }

        $out = $this->query();
        if ($out !== null) {
            $this->appCache->save(self::CACHE_KEY, $out, [Tags::VENDOR], AppCache::MAX_TTL);
        }

        return $out ?? [];
    }

    /**
     * @return array<int, array{rating: float|null, review_count: int}>|null null when the query failed
     */
    private function query(): ?array
    {
        $out = [];
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
                ->where('pe.vendor_id > ?', 0)
                ->group('pe.vendor_id');

            foreach ($connection->fetchAll($select) as $row) {
                $percent = (float) $row['pct'];
                $out[(int) $row['vendor_id']] = [
                    //  Reviews without a single star vote average 0: that is "unrated", not "0 stars".
                    'rating' => $percent > 0 ? round($percent / 20, 1) : null,
                    'review_count' => (int) $row['total'],
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: seller ratings unavailable: ' . $e->getMessage());

            return null;
        }

        return $out;
    }
}
