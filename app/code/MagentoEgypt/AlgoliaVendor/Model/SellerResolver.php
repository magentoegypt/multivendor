<?php
declare(strict_types=1);

namespace MagentoEgypt\AlgoliaVendor\Model;

use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

/**
 * The one place that answers "who sells this product, and are they live?".
 *
 * `catalog_product_entity.vendor_id` is a STATIC column — not an EAV attribute —
 * holding `ves_vendor_entity.entity_id`. The display name is
 * `ves_vendor_entity.company`, also a plain column rather than one of the
 * module's EAV attributes. That mapping is the same one
 * MagentoEgypt\VendorExtend\Model\Layer\Filter\Vendor and
 * MagentoEgypt\HomeSections\ViewModel\VendorNames rely on.
 *
 * The whole vendor table is ~36 rows on this install, so it is read once per
 * request and held in memory. A per-product lookup would fire 2,300 queries per
 * full reindex for data that changes when a seller signs up.
 */
class SellerResolver
{
    /**
     * ves_vendor_entity.status, from Vnecoms\Vendors\Model\Vendor.
     *
     * These are duplicated rather than imported because this module must be
     * loadable for a `bin/magento module:status` or a di:compile on an install
     * where Vnecoms is disabled; a class constant reference would fatal there.
     */
    public const STATUS_PENDING  = 1;
    public const STATUS_APPROVED = 2;
    public const STATUS_DISABLED = 3;
    public const STATUS_EXPIRED  = 4;

    /** Vnecoms product approval — Vnecoms\Vendors\Model\Product\Approval::STATUS_APPROVED. */
    public const PRODUCT_APPROVED = 2;

    /** catalog_product entity type; hard-coded for the same reason as the status constants. */
    private const CATALOG_PRODUCT_ENTITY_TYPE_ID = 4;

    /** @var array<int, array{status:int, company:string, key:string}>|null */
    private ?array $sellers = null;

    /** '' means "looked up and unavailable", null means "not looked up yet". */
    private ?string $approvalGate = null;

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Only APPROVED sellers are live. Pending, disabled and expired are not.
     */
    public function isLive(int $vendorId): bool
    {
        $row = $this->all()[$vendorId] ?? null;

        return $row !== null && $row['status'] === self::STATUS_APPROVED;
    }

    /**
     * The name a shopper should see, resolved exactly as the storefront does:
     * company, then the humanised url key.
     *
     * `store_name` is deliberately not consulted. It is the attribute the
     * Vnecoms templates reach for first, but it does not exist on this install
     * and returns '' for every seller — see the note in
     * Vnecoms_Vendors/templates/profile/seller-line.phtml.
     *
     * Returns the RAW name. Translation is the caller's job, because it is only
     * correct while the target store is emulated.
     */
    public function getRawName(int $vendorId): ?string
    {
        $row = $this->all()[$vendorId] ?? null;

        if ($row === null) {
            return null;
        }
        /*
         * `?:` and not a `!== ''` check, deliberately.
         *
         * Two sellers on this install carry the literal string "0" as their
         * company. "0" is falsy, so the storefront's own fallback chain in
         * seller-line.phtml and profile/title.phtml skips it and shows the url
         * key instead — "V3S2" and "V8S2". An empty-string test here would index
         * them as "0", and the seller facet would then disagree with the name on
         * the product card and the seller's own page for the same seller.
         *
         * A facet label has to be the name the shopper has already been shown.
         */
        return (trim($row['company']) ?: $this->publicName($row['key'])) ?: null;
    }

    /**
     * The seller's url key — what routes their microsite, and the only
     * identifier every row is guaranteed to have.
     */
    public function getUrlKey(int $vendorId): ?string
    {
        $key = trim($this->all()[$vendorId]['key'] ?? '');

        return $key !== '' ? $key : null;
    }

    /**
     * Sellers who must NOT have their products indexed.
     *
     * @return int[]
     */
    public function getNonLiveIds(): array
    {
        $out = [];

        foreach ($this->all() as $id => $row) {
            if ($row['status'] !== self::STATUS_APPROVED) {
                $out[] = $id;
            }
        }

        return $out;
    }

    /**
     * Same humanising as MagentoEgypt\HomeSections\ViewModel\VendorNames and
     * the seller-line template: "test_1" -> "Test 1".
     *
     * Existing capitalisation is preserved so "MIA" and "ENARA" survive intact.
     */
    private function publicName(string $key): string
    {
        $words = array_filter(
            preg_split('/\s+/', trim(str_replace(['_', '-', '.'], ' ', $key))) ?: [],
            static fn (string $w): bool => $w !== ''
        );

        return implode(' ', array_map(
            static fn (string $w): string => $w === mb_strtolower($w, 'UTF-8')
                ? mb_convert_case($w, MB_CASE_TITLE, 'UTF-8')
                : $w,
            $words
        ));
    }

    /**
     * @return array<int, array{status:int, company:string, key:string}>
     */
    private function all(): array
    {
        if ($this->sellers !== null) {
            return $this->sellers;
        }

        try {
            $connection = $this->resource->getConnection();
            $rows = $connection->fetchAll(
                $connection->select()->from(
                    ['v' => $this->resource->getTableName('ves_vendor_entity')],
                    ['entity_id', 'status', 'company', 'vendor_id']
                )
            );
        } catch (\Throwable $e) {
            /*
             * An empty map makes isLive() false for every seller, which would
             * empty the index. The observers therefore treat an empty map as
             * "no opinion" and index everything rather than nothing — a stale
             * seller on the site beats an empty catalogue.
             */
            $this->logger->error('AlgoliaVendor: could not read sellers: ' . $e->getMessage());

            return $this->sellers = [];
        }

        $map = [];

        foreach ($rows as $row) {
            $id = (int) $row['entity_id'];

            if ($id < 1) {
                continue;
            }
            $map[$id] = [
                'status'  => (int) $row['status'],
                'company' => (string) ($row['company'] ?? ''),
                'key'     => (string) ($row['vendor_id'] ?? ''),
            ];
        }

        return $this->sellers = $map;
    }

    public function isEmpty(): bool
    {
        return $this->all() === [];
    }

    /**
     * SQL gate for Vnecoms' PRODUCT approval, as an EXISTS fragment.
     *
     * Seller status and product approval are two different gates. A product can
     * sit on a fully approved seller and still be awaiting approval itself —
     * 13 do on this install, and 10 of them reached the Algolia index because
     * this module only ever filtered on the seller.
     *
     * MagentoEgypt\VendorExtend\Model\StorefrontVisibility::baseSelect() is the
     * canonical gate (`appr.value = 2` INNER JOINed, plus inactive vendors), and
     * MagentoEgypt\VendorExtend\Plugin\Indexer\ExcludeUnapprovedProducts applies
     * it to the OpenSearch index. That plugin hooks an Elasticsearch-only class,
     * so it does nothing for Algolia — this is the Algolia-side equivalent, and
     * it deliberately mirrors the INNER JOIN semantics: a product with no
     * approval row is excluded, exactly as OpenSearch excludes it.
     *
     * Returned as a fragment rather than an id list because the observer applies
     * it to a collection that has not loaded yet; there is no id set to pass.
     *
     * @return string|null null when the attribute is absent — then this gate is
     *                     simply not applied, rather than emptying the index.
     */
    public function getApprovalGateSql(): ?string
    {
        if ($this->approvalGate !== null) {
            return $this->approvalGate ?: null;
        }

        try {
            $connection = $this->resource->getConnection();
            $attributeId = (int) $connection->fetchOne(
                $connection->select()
                    ->from($this->resource->getTableName('eav_attribute'), ['attribute_id'])
                    ->where('attribute_code = ?', 'approval')
                    ->where('entity_type_id = ?', self::CATALOG_PRODUCT_ENTITY_TYPE_ID)
            );

            if ($attributeId < 1) {
                $this->approvalGate = '';

                return null;
            }

            $table = $connection->quoteIdentifier(
                $this->resource->getTableName('catalog_product_entity_int')
            );

            $this->approvalGate = sprintf(
                'EXISTS (SELECT 1 FROM %s AS hm_appr'
                . ' WHERE hm_appr.entity_id = e.entity_id AND hm_appr.attribute_id = %d'
                . ' AND hm_appr.store_id = 0 AND hm_appr.value = %d)',
                $table,
                $attributeId,
                self::PRODUCT_APPROVED
            );
        } catch (\Throwable $e) {
            $this->logger->error('AlgoliaVendor: approval gate unavailable: ' . $e->getMessage());
            $this->approvalGate = '';

            return null;
        }

        return $this->approvalGate;
    }
}
