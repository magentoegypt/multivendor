<?php
/**
 * Hub Market — "New Stores" row.
 *
 * The Figma reference shows recently-joined vendors. This reads the real Vnecoms
 * vendor table ordered by created_at, filtered to APPROVED vendors only.
 *
 * The approval filter is not cosmetic. Showing unvetted applicants on the
 * storefront would contradict the homepage's own "Every seller is reviewed and
 * approved before going live" promise.
 *
 * GET THE STATUS VALUE RIGHT — it is counter-intuitive and I got it wrong first
 * time. Vnecoms\Vendors\Model\Vendor defines:
 *
 *     STATUS_PENDING  = 1
 *     STATUS_APPROVED = 2      <-- approved is 2, NOT 1
 *     STATUS_DISABLED = 3
 *     STATUS_EXPIRED  = 4
 *
 * In this install 22 vendors are APPROVED (2) and only 3 are PENDING (1).
 * Filtering on 1 therefore did the exact opposite of what was intended: it hid
 * every real store and published three pending accounts — one of them literally
 * named "Test" — on the live homepage.
 *
 * The constant is referenced rather than hard-coded so this cannot drift, and it
 * matches how Vnecoms' own seller list filters
 * (module-vendors-sellerlist/Block/SellerList.php:298).
 *
 * Store name lives in the vendor EAV varchar table (attribute `store_name`),
 * with `company` on the entity row as a fallback.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Block;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;

class NewStores extends Template
{
    private ResourceConnection $resource;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * @return array<int, array{name:string,url:string,logo:?string,products:int,stars:?float,reviews:int,dispatch:?string}>
     */
    public function getStores(): array
    {
        $limit = (int) ($this->getData('limit') ?: 4);
        $conn = $this->resource->getConnection();
        $entity = $this->resource->getTableName('ves_vendor_entity');

        if (!$conn->isTableExists($entity)) {
            return [];
        }

        $approved = \defined('\Vnecoms\Vendors\Model\Vendor::STATUS_APPROVED')
            ? \Vnecoms\Vendors\Model\Vendor::STATUS_APPROVED
            : 2;

        // Only stores that ACTUALLY HAVE PRODUCTS. Ordering approved vendors by
        // join date alone surfaced "test too", "vendor" and "magentoo" — this
        // install's vendor table is full of test accounts, and sending a shopper
        // to an empty store is worse than showing one fewer card.
        //
        // vendor_id is a STATIC attribute: a real column on catalog_product_entity,
        // not an EAV value table. Joining it directly is both correct and cheap.
        $product = $this->resource->getTableName('catalog_product_entity');

        $select = $conn->select()
            ->from(['v' => $entity], ['entity_id', 'vendor_id', 'company', 'created_at'])
            ->join(['p' => $product], 'p.vendor_id = v.entity_id', ['products' => 'COUNT(p.entity_id)'])
            ->where('v.status = ?', $approved)
            ->group('v.entity_id')
            ->having('COUNT(p.entity_id) > 0')
            ->order('v.created_at DESC')
            //  RATING MODE fetches a wider pool then re-sorts in PHP.
            //  Ordering by rating in SQL is not possible here: the star value is
            //  derived from review_entity_summary via a separate query (see
            //  fetchRatings), so at this point the ranking column does not exist
            //  yet. Taking the newest N and sorting those would rank within an
            //  arbitrary slice, not across the catalogue — hence the wider pool.
            ->limit($this->isRatingOrder() ? $limit * 6 : $limit);

        $rows = $conn->fetchAll($select);
        if (!$rows) {
            return [];
        }

        $names = $this->fetchAttribute(array_column($rows, 'entity_id'), 'store_name');
        /*
         * ves_vendor_config, NOT the EAV attribute. No `logo` EAV attribute
         * exists on this install — the attribute fetch always returned nothing,
         * which is why these cards could only ever show letter discs. The vendor
         * panel writes uploads to `general/store_information/logo` in
         * ves_vendor_config with files under ves_vendors/logo/, and that is the
         * source Vnecoms' own SellerList::getSellerImageUrl() reads. One source,
         * both rails.
         */
        $logos = $this->fetchLogoConfig(array_column($rows, 'entity_id'));
        $counts  = array_column($rows, 'products', 'entity_id');
        $ratings  = $this->fetchRatings(array_column($rows, 'entity_id'));
        // Declared beats derived: a seller's own commitment outranks an average
        // computed from demo order history.
        $ids      = array_column($rows, 'entity_id');
        $dispatch = $this->fetchDeclaredDispatch($ids) + $this->fetchDispatchTimes($ids);

        $mediaUrl = $this->storeManager->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        $out = [];
        foreach ($rows as $r) {
            $id = (int) $r['entity_id'];
            $logo = $logos[$id] ?? null;
            $out[] = [
                //  `??` only falls through on NULL, and `company` is an empty
                //  STRING on four of the twenty-five vendor rows — so a seller
                //  with no store_name and a blank company stopped at the empty
                //  string and the card rendered with no name at all, just
                //  "7 products / VERIFIED". Each step is now tested for content,
                //  and the vendor code carries the card if nothing else does.
                'name'     => $this->firstNonEmpty(
                    $names[$id] ?? null,
                    $r['company'] ?? null,
                    $r['vendor_id'] ?? null
                ),
                'url'      => $this->getUrl('shop/' . $r['vendor_id']),
                'logo'     => $logo ? $mediaUrl . 'ves_vendors/logo/' . ltrim((string) $logo, '/') : null,
                'products' => (int) ($counts[$id] ?? 0),
                'stars'    => $ratings[$id]['stars'] ?? null,
                'reviews'  => $ratings[$id]['count'] ?? 0,
                'dispatch' => $dispatch[$id] ?? null,
            ];
        }

        if ($this->isRatingOrder()) {
            //  Unrated stores sink rather than disappear: a store with no reviews
            //  is not evidence of a BAD store, so it still qualifies for the rail
            //  once the rated ones are placed.
            usort($out, static function (array $a, array $b): int {
                return [$b['stars'] ?? -1, $b['reviews'] ?? 0]
                   <=> [$a['stars'] ?? -1, $a['reviews'] ?? 0];
            });
            $out = array_slice($out, 0, $limit);
        }

        return $out;
    }

    /**
     * True when this instance should rank by customer rating instead of join date.
     */
    private function isRatingOrder(): bool
    {
        return $this->getData('order_by') === 'rating';
    }

    /**
     * Read one vendor EAV varchar attribute for a set of vendors.
     *
     * JOIN eav_attribute, NOT ves_vendor_eav_attribute. The Vnecoms table holds
     * only form/display metadata (is_used_in_profile_form, sort_order, ...) keyed
     * by attribute_id — it has NO attribute_code column. Joining it and filtering
     * on a.attribute_code raises "Unknown column 'a.attribute_code'", which the
     * catch below turned into an empty array, so this method silently returned
     * nothing for every attribute it was ever asked for. That is why declared
     * dispatch times never reached the storefront.
     *
     * Also scoped to the vendor entity type: attribute_code is unique per entity
     * type, not globally, so an unscoped lookup could match a same-named
     * attribute belonging to products or customers.
     *
     * @return array<int,string> entity_id => value
     */
    /**
     * Store logos from vendor config — the table the vendor panel writes.
     *
     * @param int[] $entityIds
     * @return array<int, string> vendor id -> bare filename under ves_vendors/logo/
     */
    private function fetchLogoConfig(array $entityIds): array
    {
        if (!$entityIds) {
            return [];
        }
        try {
            $conn = $this->resource->getConnection();

            return array_filter($conn->fetchPairs(
                $conn->select()
                    ->from($this->resource->getTableName('ves_vendor_config'), ['vendor_id', 'value'])
                    ->where('path = ?', 'general/store_information/logo')
                    ->where('store_id = ?', 0)
                    ->where('vendor_id IN (?)', $entityIds)
            ));
        } catch (\Throwable $e) {
            $this->_logger->warning('Hub Market store logos: ' . $e->getMessage());
            return [];
        }
    }

    private function fetchAttribute(array $entityIds, string $code): array
    {
        $conn = $this->resource->getConnection();
        $eav = $this->resource->getTableName('eav_attribute');
        $val = $this->resource->getTableName('ves_vendor_entity_varchar');
        if (!$entityIds || !$conn->isTableExists($eav) || !$conn->isTableExists($val)) {
            return [];
        }
        try {
            $select = $conn->select()
                ->from(['v' => $val], ['entity_id', 'value'])
                ->join(['a' => $eav], 'a.attribute_id = v.attribute_id', [])
                ->join(
                    ['et' => $this->resource->getTableName('eav_entity_type')],
                    'et.entity_type_id = a.entity_type_id',
                    []
                )
                ->where('et.entity_type_code = ?', \Vnecoms\Vendors\Model\Vendor::ENTITY)
                ->where('a.attribute_code = ?', $code)
                ->where('v.entity_id IN (?)', $entityIds);
            return $conn->fetchPairs($select);
        } catch (\Throwable $e) {
            // Log it. Swallowing this silently is exactly how the broken join
            // above went unnoticed.
            $this->_logger->warning(
                sprintf('Hub Market vendor attribute "%s": %s', $code, $e->getMessage())
            );
            return [];
        }
    }



    /**
     * Average star rating per vendor, from REAL approved product reviews.
     *
     * review_entity_summary.rating_summary is a PERCENTAGE (0-100), not a star
     * value — converting it needs /20, and treating it as a 0-5 number is the
     * obvious way to publish a wrong rating. There are 373 approved reviews on
     * this install, so this is genuine customer data, not a placeholder.
     *
     * Vendors with no reviews return null and the card simply omits the stars
     * rather than showing a hopeful default.
     *
     * @return array<int, array{stars:float,count:int}>
     */
    private function fetchRatings(array $entityIds): array
    {
        if (!$entityIds) {
            return [];
        }
        $conn = $this->resource->getConnection();
        try {
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
                //  Default-scope summary only — the store-2 rows are all zero and
                //  dilute the average. Same fix as VendorMeta::loadRatings().
                ->where('s.store_id = ?', 0)
                ->where('pe.vendor_id IN (?)', $entityIds)
                ->group('pe.vendor_id');

            $out = [];
            foreach ($conn->fetchAll($select) as $row) {
                $out[(int) $row['vendor_id']] = [
                    'stars' => round(((float) $row['pct']) / 20, 1),   // percent -> /5
                    'count' => (int) $row['total'],
                ];
            }
            return $out;
        } catch (\Throwable $e) {
            $this->_logger->warning('Hub Market store ratings: ' . $e->getMessage());
            return [];
        }
    }


    /**
     * Typical dispatch time per vendor, from REAL shipments.
     *
     * Measured as order.created_at -> shipment.created_at. There are 37 shipments
     * across 34 orders here, so the signal exists — but it is NOT uniformly
     * trustworthy: raw averages range from 0.0 hours to 69 DAYS because these are
     * demo orders shipped at arbitrary times.
     *
     * Publishing "ships in 69 days" would mislead a shopper worse than showing
     * nothing, so a vendor only gets a badge when the number is credible:
     *
     *   - at least MIN_SHIPMENTS shipments (one lucky dispatch proves nothing)
     *   - an average at or under MAX_PLAUSIBLE_DAYS
     *
     * Anything outside that returns null and the card omits the line. This is a
     * DERIVED figure, not a merchant-declared SLA — if sellers should promise a
     * dispatch window, that wants a real vendor attribute they control.
     *
     * @return array<int,string> vendor entity_id => human label
     */

    /**
     * Seller-DECLARED dispatch time, which outranks anything derived.
     *
     * A promise the seller made beats an average computed from their history:
     * the derived figure describes what happened, this describes what they
     * commit to. Sellers set it in the seller panel (the attribute is `visible`
     * and `user_defined`, so Vnecoms renders it in the account form).
     *
     * @return array<int,string> vendor entity_id => human label
     */
    private function fetchDeclaredDispatch(array $entityIds): array
    {
        if (!$entityIds) {
            return [];
        }
        $labels = [
            'same_day' => (string) __('Ships same day'),
            'next_day' => (string) __('Ships next business day'),
            'days_2_3' => (string) __('Ships in 2-3 business days'),
            'days_3_5' => (string) __('Ships in 3-5 business days'),
            'days_5_7' => (string) __('Ships in 5-7 business days'),
        ];
        $raw = $this->fetchAttribute($entityIds, 'dispatch_time');
        $out = [];
        foreach ($raw as $id => $value) {
            if ($value !== null && $value !== '' && isset($labels[$value])) {
                $out[(int) $id] = $labels[$value];
            }
        }
        return $out;
    }

    private function fetchDispatchTimes(array $entityIds): array
    {
        $minShipments = 2;
        $maxDays      = 7;

        if (!$entityIds) {
            return [];
        }
        $conn = $this->resource->getConnection();
        try {
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
                ->where('pe.vendor_id IN (?)', $entityIds)
                ->group('pe.vendor_id');

            $out = [];
            foreach ($conn->fetchAll($select) as $row) {
                $ships = (int) $row['shipments'];
                $hours = (float) $row['avg_hours'];
                if ($ships < $minShipments || $hours < 0 || ($hours / 24) > $maxDays) {
                    continue;   // not credible - omit rather than mislead
                }
                if ($hours < 24) {
                    $label = (string) __('Usually ships same day');
                } elseif ($hours < 48) {
                    $label = (string) __('Usually ships next day');
                } else {
                    $label = (string) __('Usually ships in %1 days', (int) ceil($hours / 24));
                }
                $out[(int) $row['vendor_id']] = $label;
            }
            return $out;
        } catch (\Throwable $e) {
            $this->_logger->warning('Hub Market dispatch times: ' . $e->getMessage());
            return [];
        }
    }

    public function getCacheKeyInfo(): array
    {
        //  order_by MUST be in the key. Two instances of this block now render on
        //  the homepage — "New Stores" (by join date) and "Top Vendors This Month"
        //  (by rating) — and without this they would collide on one cache entry
        //  and the second rail would silently serve the first one's HTML.
        return ['HM_HOME_NEW_STORES', $this->storeManager->getStore()->getId(),
                (int) ($this->getData('limit') ?: 4),
                (string) ($this->getData('order_by') ?: 'created')];
    }

    protected function getCacheLifetime(): ?int
    {
        return 1800;
    }
    /**
     * First argument that actually holds text, trimmed.
     */
    private function firstNonEmpty(mixed ...$candidates): string
    {
        foreach ($candidates as $candidate) {
            $value = trim((string) ($candidate ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

}
