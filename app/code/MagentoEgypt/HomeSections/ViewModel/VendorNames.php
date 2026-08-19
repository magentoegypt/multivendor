<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\ViewModel;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Psr\Log\LoggerInterface;

/**
 * Product -> selling vendor, for the vendor line on a product card.
 *
 * The Figma reference prints the seller above every product title ("TechGear Pro",
 * "HomeStyle Living"). On a marketplace that is not decoration: it is the one piece
 * of information that distinguishes two identical listings from different sellers,
 * and it was missing from every card on the storefront.
 *
 * WHY A BATCH MAP AND NOT A LOOKUP PER PRODUCT
 * --------------------------------------------
 * `catalog_product_entity.vendor_id` is a STATIC attribute — a real column on the
 * entity table — holding `ves_vendor_entity.entity_id`. Resolving it per card
 * through the vendor repository would be one query per product, so ~50 queries on
 * a homepage with nine rails. This install has 25 vendor rows in total, so the
 * whole table is fetched ONCE per request and held here.
 *
 * The name lives in `ves_vendor_entity.company`, which is a plain column rather
 * than one of the module's EAV attributes. Some rows have it empty (vendor 1 on
 * this install), so the url-key falls in behind it — never an empty vendor line
 * and never a bare numeric id.
 *
 * Reads the connection directly rather than going through the Vnecoms vendor
 * collection deliberately: that collection loads the full EAV row set for every
 * vendor, which is the cost this class exists to avoid.
 */
class VendorNames implements ArgumentInterface
{
    /** @var array<int, array{name: string, key: string}>|null */
    private ?array $map = null;

    /** @var array<int, array{products:int, stars:float|null, reviews:int}>|null */
    private ?array $stats = null;

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Display name for a vendor id, or null when the product has no seller.
     *
     * Products created in admin rather than by a vendor carry vendor_id 0 — 91 of
     * the 2304 products here. Those genuinely have no seller to name, so the card
     * omits the line rather than inventing one.
     */
    public function getName(mixed $vendorId): ?string
    {
        $id = (int) $vendorId;
        if ($id <= 0) {
            return null;
        }

        return $this->load()[$id]['name'] ?? null;
    }

    /**
     * Headline stats for one vendor: how many products, and the star rating.
     *
     * The PDP vendor card shows "4.8 * . 156 products". Both numbers are real:
     * the count is the seller's live, browsable catalogue and the stars come from
     * approved customer reviews. A vendor with no reviews returns null stars and
     * the card omits them rather than printing a hopeful 0 or 5.
     *
     * Batched for the same reason the name map is — one query for the whole
     * table, not one per card, so a page with several vendor references does not
     * pay per reference.
     *
     * @return array{products:int, stars:float|null, reviews:int}
     */
    public function getStats(mixed $vendorId): array
    {
        $id = (int) $vendorId;
        $empty = ['products' => 0, 'stars' => null, 'reviews' => 0];
        if ($id <= 0) {
            return $empty;
        }

        return $this->loadStats()[$id] ?? $empty;
    }

    /**
     * @return array<int, array{products:int, stars:float|null, reviews:int}>
     */
    private function loadStats(): array
    {
        if ($this->stats !== null) {
            return $this->stats;
        }

        $this->stats = [];

        try {
            $connection = $this->resource->getConnection();
            $product    = $this->resource->getTableName('catalog_product_entity');
            $intTable   = $this->resource->getTableName('catalog_product_entity_int');
            $attribute  = $this->resource->getTableName('eav_attribute');
            $entityType = $this->resource->getTableName('eav_entity_type');

            $attrId = static function (string $code) use ($connection, $attribute, $entityType) {
                return $connection->select()
                    ->from(['a' => $attribute], ['attribute_id'])
                    ->join(['e' => $entityType], 'e.entity_type_id = a.entity_type_id', [])
                    ->where('e.entity_type_code = ?', 'catalog_product')
                    ->where('a.attribute_code = ?', $code);
            };

            /*
             * Counts only what a shopper can actually reach: enabled, and not
             * "Not Visible Individually". Counting the raw vendor_id column would
             * include disabled products and every configurable's hidden children,
             * which is how a seller with 6 listings ends up advertising 40.
             */
            $counts = $connection->select()
                ->from(['p' => $product], ['vendor_id', 'products' => 'COUNT(*)'])
                ->joinInner(
                    ['st' => $intTable],
                    'st.entity_id = p.entity_id AND st.store_id = 0 AND st.attribute_id = (' . $attrId('status') . ')',
                    []
                )
                ->joinInner(
                    ['vis' => $intTable],
                    'vis.entity_id = p.entity_id AND vis.store_id = 0 AND vis.attribute_id = (' . $attrId('visibility') . ')',
                    []
                )
                ->where('p.vendor_id > 0')
                ->where('st.value = ?', 1)
                ->where('vis.value > ?', 1)
                ->group('p.vendor_id');

            foreach ($connection->fetchAll($counts) as $row) {
                $this->stats[(int) $row['vendor_id']] = [
                    'products' => (int) $row['products'],
                    'stars'    => null,
                    'reviews'  => 0,
                ];
            }

            /*
             * review_entity_summary.rating_summary is a PERCENTAGE (0-100), not a
             * star value — /20 converts it. Treating it as 0-5 is the obvious way
             * to publish a wrong rating. Same conversion as HomeSections\Block\NewStores.
             */
            $ratings = $connection->select()
                ->from(['pe' => $product], ['vendor_id'])
                ->join(['r' => $this->resource->getTableName('review')], 'r.entity_pk_value = pe.entity_id', [])
                ->join(
                    ['s' => $this->resource->getTableName('review_entity_summary')],
                    's.entity_pk_value = pe.entity_id',
                    ['pct' => 'AVG(s.rating_summary)', 'total' => 'COUNT(DISTINCT r.review_id)']
                )
                ->where('r.status_id = ?', 1)
                ->where('pe.vendor_id > 0')
                ->group('pe.vendor_id');

            foreach ($connection->fetchAll($ratings) as $row) {
                $id = (int) $row['vendor_id'];
                $this->stats[$id] ??= ['products' => 0, 'stars' => null, 'reviews' => 0];
                $this->stats[$id]['stars']   = round(((float) $row['pct']) / 20, 1);
                $this->stats[$id]['reviews'] = (int) $row['total'];
            }
        } catch (LocalizedException | \Throwable $e) {
            //  The card renders without stats rather than taking the PDP down.
            $this->logger->warning('HomeSections: vendor stats unavailable: ' . $e->getMessage());
            $this->stats = [];
        }

        return $this->stats;
    }

    /**
     * Storefront URL for the vendor's shop page, or null if it cannot be built.
     */
    public function getUrlKey(mixed $vendorId): ?string
    {
        $id = (int) $vendorId;
        if ($id <= 0) {
            return null;
        }

        return $this->load()[$id]['key'] ?? null;
    }

    /**
     * Last-resort display name for a seller who has never filled in `company`.
     *
     * Four vendors on this install have an empty company, and the fallback used
     * to print the raw url key — so the cart grouped items under "test_1" and the
     * rails credited "magento_tester". That is an internal identifier, not a
     * name a shopper should ever be shown.
     *
     * The key is the only other thing every vendor is guaranteed to have (it is
     * what routes their shop page), so it is formatted into something readable
     * rather than replaced with an invented name: separators become spaces and
     * all-lowercase words are capitalised. "test_1" -> "Test 1".
     *
     * Existing capitalisation is preserved, so acronyms and brand casing that
     * sellers chose themselves survive — "MIA" and "ENARA" are not flattened to
     * "Mia" and "Enara".
     *
     * The result still goes through __(), so a merchant can name any of these
     * four properly from the theme's i18n CSV without touching code — and
     * filling in `company` in the vendor panel takes precedence over all of it.
     */
    private function publicName(string $key): string
    {
        $words = preg_split('/\s+/', trim(str_replace(['_', '-', '.'], ' ', $key))) ?: [];

        $words = array_map(
            static fn(string $w): string => $w === mb_strtolower($w, 'UTF-8')
                ? mb_convert_case($w, MB_CASE_TITLE, 'UTF-8')
                : $w,
            array_filter($words, static fn(string $w): bool => $w !== '')
        );

        return implode(' ', $words);
    }

    /**
     * @return array<int, array{name: string, key: string}>
     */
    private function load(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        $this->map = [];

        try {
            $connection = $this->resource->getConnection();
            $table      = $this->resource->getTableName('ves_vendor_entity');
            $select     = $connection->select()->from($table, ['entity_id', 'vendor_id', 'company']);

            foreach ($connection->fetchAll($select) as $row) {
                $key  = trim((string) ($row['vendor_id'] ?? ''));
                $name = trim((string) ($row['company'] ?? ''));

                /*
                 * `company` is free text the seller typed, and vendor data has NO
                 * store scope — ves_vendor_entity_varchar carries no store_id — so
                 * the one stored value feeds both storefronts and the seller's own
                 * panel. An Arabic company name therefore rendered on the English
                 * store. Localised here rather than rewritten in the table, so the
                 * Arabic store keeps the Arabic and the seller keeps their name.
                 * Mapping lives in the theme i18n CSVs; unmapped names pass through.
                 */
                $this->map[(int) $row['entity_id']] = [
                    'name' => (string) __($name !== '' ? $name : $this->publicName($key)),
                    'key'  => $key,
                ];
            }
        } catch (LocalizedException | \Throwable $e) {
            /*
             * A missing or renamed vendor table must not take the homepage down —
             * the vendor line is an enhancement to a card that renders fine
             * without it. Logged rather than swallowed so it is still visible.
             */
            $this->logger->warning('HomeSections: vendor name map unavailable: ' . $e->getMessage());
            $this->map = [];
        }

        return $this->map;
    }
}
