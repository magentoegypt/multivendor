<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\ViewModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;

/**
 * Star rating and dispatch time for a vendor, for the Featured Stores rail.
 *
 * WHY THIS EXISTS
 * ---------------
 * QA cycle 1 asked Featured Stores to show "the star rating (e.g. 4.8) and
 * estimated delivery time (30-60 min) alongside the product count". That rail is
 * rendered by a Vnecoms widget block whose API exposes a name, a logo, a URL and
 * a product count — and nothing else. The same two figures ARE computed for the
 * Top Vendors rail, but by PRIVATE methods on MagentoEgypt\HomeSections\Block\
 * NewStores, which cannot be reached from another block.
 *
 * WHY A VIEW MODEL AND NOT A SHARED PARENT
 * ----------------------------------------
 * Lifting those methods into a base class or injecting a service into NewStores
 * both change an existing constructor, and a constructor change does not take
 * effect in production until `setup:di:compile` runs — which wipes `generated/`
 * and 500s the storefront for the duration. A NEW class has no compiled
 * definition to be stale, so the object manager resolves it by reflection and it
 * works on deploy. Same route ReviewStars takes.
 *
 * NewStores keeps its own copy for now. That duplication is deliberate and
 * temporary: it should adopt this class at the next di:compile window, and the
 * queries here are written to be the ones that survive.
 *
 * ONE QUERY PER RAIL, NOT PER CARD. Both lookups are batched over the whole set
 * of vendor ids and memoised, because the alternative is a query per card and
 * this rail renders on the homepage.
 */
class VendorMeta implements ArgumentInterface
{
    /** Dispatch is only quoted when enough real shipments back it up. */
    private const MIN_SHIPMENTS = 2;
    private const MAX_DAYS      = 7;

    /** @var array<int, array{stars: float|null, reviews: int}>|null */
    private ?array $ratings = null;

    /** @var array<int, string>|null */
    private ?array $dispatch = null;

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Average stars out of 5, or null when the vendor has no rated product.
     */
    public function getStars(int $vendorId): ?float
    {
        return $this->loadRatings()[$vendorId]['stars'] ?? null;
    }

    public function getReviewCount(int $vendorId): int
    {
        return (int) ($this->loadRatings()[$vendorId]['reviews'] ?? 0);
    }

    /**
     * A human dispatch estimate, or '' when there is nothing honest to say.
     */
    public function getDispatch(int $vendorId): string
    {
        return $this->loadDispatch()[$vendorId] ?? '';
    }

    /**
     * Ratings for every vendor at once, averaged over their products' review
     * summaries. Approved reviews only — status_id 1 — or a pending review would
     * move a public star rating.
     *
     * @return array<int, array{stars: float|null, reviews: int}>
     */
    private function loadRatings(): array
    {
        if ($this->ratings !== null) {
            return $this->ratings;
        }
        $this->ratings = [];

        try {
            $conn = $this->resource->getConnection();
            $select = $conn->select()
                ->from(['pe' => $this->resource->getTableName('catalog_product_entity')], ['vendor_id'])
                ->join(['r' => $this->resource->getTableName('review')],
                    'r.entity_pk_value = pe.entity_id', [])
                ->join(['s' => $this->resource->getTableName('review_entity_summary')],
                    's.entity_pk_value = pe.entity_id', [
                        'pct'   => 'AVG(s.rating_summary)',
                        'total' => 'COUNT(DISTINCT r.review_id)',
                    ])
                ->where('r.status_id = ?', 1)
                /*
                 * DEFAULT-SCOPE SUMMARY ONLY. review_entity_summary carries one
                 * row per store (0,1,2,3 here), and the store-2 rows — the
                 * vendor-panel store — hold rating_summary 0 for every product.
                 * Joined unfiltered they enter the average and drag a vendor of
                 * 4-5 star products down to ~2.2. Measured on this install, and
                 * it is the same trap as review_store vs rating_store: a
                 * per-store table quietly multiplying a join.
                 */
                ->where('s.store_id = ?', 0)
                ->where('pe.vendor_id IS NOT NULL')
                ->group('pe.vendor_id');

            foreach ($conn->fetchAll($select) as $row) {
                $this->ratings[(int) $row['vendor_id']] = [
                    //  rating_summary is a PERCENTAGE despite the name; /20 for a
                    //  score out of five.
                    'stars'   => round(((float) $row['pct']) / 20, 1),
                    'reviews' => (int) $row['total'],
                ];
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Hub Market vendor meta (ratings): ' . $e->getMessage());
        }

        return $this->ratings;
    }

    /**
     * Dispatch time, preferring what the merchant DECLARED over what we measured.
     *
     * A declared value is a promise the vendor has made and is the one to show.
     * The measured fallback is the mean order-to-shipment gap over that vendor's
     * real shipments, and it is only quoted when there are at least
     * MIN_SHIPMENTS of them — one fast shipment is not a delivery estimate — and
     * when the answer lands inside MAX_DAYS, beyond which "ships in 30 days" is
     * worse than saying nothing.
     *
     * @return array<int, string>
     */
    private function loadDispatch(): array
    {
        if ($this->dispatch !== null) {
            return $this->dispatch;
        }
        $this->dispatch = [];

        /*
         * THE DURATION ONLY, not a sentence (CL036-QA01 item 12).
         *
         * These read "Ships in 2-3 business days" and sat on a stats line beside
         * the product count, on a card 139px wide at 390px. Nothing that long
         * fits, so the line broke mid-phrase — "Ships within" above "24h" — and
         * threw the cards it happened to on 22px taller than their neighbours.
         * That ragged rail is what the tester filed.
         *
         * The reference's equivalent is "30–60 min": a clock glyph and a
         * duration, no verb. The glyph is already there and already carries the
         * meaning, so the verb was only ever costing width. The full sentence is
         * kept as the element's title in the template, for anyone who wants it.
         */
        $declaredLabels = [
            'same_day' => (string) __('Same day'),
            'next_day' => (string) __('Next business day'),
            'days_2_3' => (string) __('2-3 days'),
            'days_3_5' => (string) __('3-5 days'),
            'days_5_7' => (string) __('5-7 days'),
        ];

        try {
            $conn = $this->resource->getConnection();

            //  1. declared, from the vendor's own dispatch_time attribute
            $attr = (int) $conn->fetchOne(
                $conn->select()
                    ->from($this->resource->getTableName('eav_attribute'), ['attribute_id'])
                    ->where('attribute_code = ?', 'dispatch_time')
                    ->limit(1)
            );
            if ($attr) {
                $rows = $conn->fetchPairs(
                    $conn->select()
                        ->from($this->resource->getTableName('ves_vendor_entity_varchar'),
                            ['entity_id', 'value'])
                        ->where('attribute_id = ?', $attr)
                );
                foreach ($rows as $id => $value) {
                    if (isset($declaredLabels[$value])) {
                        $this->dispatch[(int) $id] = $declaredLabels[$value];
                    }
                }
            }

            //  2. measured, for vendors that declared nothing
            $select = $conn->select()
                ->from(['pe' => $this->resource->getTableName('catalog_product_entity')], ['vendor_id'])
                ->join(['oi' => $this->resource->getTableName('sales_order_item')],
                    'oi.product_id = pe.entity_id', [])
                ->join(['o' => $this->resource->getTableName('sales_order')],
                    'o.entity_id = oi.order_id', [])
                ->join(['sh' => $this->resource->getTableName('sales_shipment')],
                    'sh.order_id = o.entity_id', [
                        'shipments' => 'COUNT(DISTINCT sh.entity_id)',
                        'avg_hours' => 'AVG(TIMESTAMPDIFF(HOUR, o.created_at, sh.created_at))',
                    ])
                ->where('pe.vendor_id IS NOT NULL')
                ->group('pe.vendor_id');

            foreach ($conn->fetchAll($select) as $row) {
                $id = (int) $row['vendor_id'];
                if (isset($this->dispatch[$id])) {
                    continue;                       // declared wins
                }
                if ((int) $row['shipments'] < self::MIN_SHIPMENTS) {
                    continue;
                }
                $hours = (float) $row['avg_hours'];
                if ($hours <= 0 || $hours > self::MAX_DAYS * 24) {
                    continue;
                }
                $this->dispatch[$id] = $hours <= 24
                    ? (string) __('24h')
                    : (string) __('~%1 days', (int) ceil($hours / 24));
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Hub Market vendor meta (dispatch): ' . $e->getMessage());
        }

        return $this->dispatch;
    }
}
