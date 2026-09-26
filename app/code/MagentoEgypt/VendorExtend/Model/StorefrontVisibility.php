<?php
/**
 * Hub Market — "will the storefront actually serve this product?"
 *
 * THE PROBLEM THIS ANSWERS
 * -----------------------
 * Vnecoms hides unapproved vendor products from the storefront by bolting an
 * `approval` condition onto the product COLLECTION — see
 * Vnecoms\VendorsProduct\Model\Plugin\Layer::beforePrepareProductCollection()
 * and MagentoEgypt\VendorExtend\Plugin\Helper\VendorsProduct\Data, which
 * narrows the allowed set to APPROVED alone on the frontend.
 *
 * That works only where the collection is the whole story. Two places on this
 * build it is not, and both were reported as bugs by the client:
 *
 *   1. THE SEARCH INDEX PAGES BEFORE THE SQL FILTERS RUN. Category listings go
 *      through the fulltext collection: OpenSearch returns a page of ids, then
 *      the SQL adds `approval = 2`. Anything the index holds but the SQL drops
 *      leaves a HOLE in that page — page 1 of /all.html rendered 5 cards out of
 *      12 while the pager still counted six pages ([CL036-DEV01.21]).
 *
 *   2. CONFIGURABLE PARENTS ARE INJECTED AFTER THE FILTERS.
 *      Magento\ConfigurableProduct\Plugin\CatalogWidget\Block\Product\
 *      ProductsListPlugin::afterCreateCollection() appends the parents of any
 *      matched child with `$result->addItem($item->load($item->getId()))` —
 *      no approval check, no status check, only visibility. An unapproved
 *      parent therefore reached the "Popular Products" rail, and clicking it
 *      landed on a 404 ([CL036-DEV01.22]).
 *
 * So the gate has to be answerable OUTSIDE a collection. One query, given ids.
 *
 * FAIL OPEN, NEVER CLOSED. If the `approval` attribute or the `vendor_id`
 * column is missing — Vnecoms uninstalled, a partially migrated database — the
 * gate returns everything it was given. The cost of a wrong "no" here is a
 * product silently vanishing from the catalog and the search index; the cost of
 * a wrong "yes" is the status quo.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;
use Vnecoms\Vendors\Helper\Data as VendorHelper;
use Vnecoms\VendorsProduct\Model\Source\Approval;

class StorefrontVisibility
{
    /**
     * The one approval value the storefront shows.
     *
     * Deliberately a constant rather than Vnecoms' getAllowedApprovalStatus().
     * That helper answers [APPROVED, PENDING_UPDATE] by default and is narrowed
     * to [APPROVED] by a plugin registered in etc/frontend/di.xml — so it gives
     * a DIFFERENT answer to the indexer, which runs in the crontab/global area.
     * An index built on the wider set would put PENDING_UPDATE products back in
     * the pages the storefront then filters out, which is the exact hole this
     * class exists to close. If that plugin is ever widened, widen this too.
     */
    private const STOREFRONT_APPROVAL = Approval::STATUS_APPROVED;

    /** @var int[]|null */
    private ?array $inactiveVendorIds = null;

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly EavConfig $eavConfig,
        private readonly VendorHelper $vendorHelper,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * The subset of $productIds that is approved and owned by an active vendor.
     *
     * Store-independent: `approval` is a global attribute and vendor status is
     * not scoped at all. This is the gate for the SEARCH INDEX, where Magento
     * has already applied status and visibility for the store being indexed.
     *
     * @param int[] $productIds
     * @return int[] in the order given
     */
    public function approvedIds(array $productIds): array
    {
        if (!$productIds) {
            return [];
        }

        try {
            $select = $this->baseSelect($productIds);
            if ($select === null) {
                return $productIds;
            }

            $allowed = array_map('intval', $this->resource->getConnection()->fetchCol($select));
        } catch (\Throwable $e) {
            $this->logger->error('StorefrontVisibility::approvedIds failed, passing all ids: ' . $e->getMessage());

            return $productIds;
        }

        $allowed = array_flip($allowed);

        return array_values(array_filter($productIds, static fn ($id) => isset($allowed[(int) $id])));
    }

    /**
     * approvedIds() minus Vnecoms "select and sell" copies: the products the search index holds.
     *
     * A copy is another seller's offer on an existing product (select_from_product_id = the
     * original). The storefront never lists one (Vnecoms_VendorsPriceComparison's Layer plugin
     * filters them out, and the product page compares the sellers instead), and Algolia excludes
     * them (AlgoliaVendor ExcludeInactiveSellerProducts). OpenSearch still held the 5 live ones, so
     * once storefront GraphQL moved to OpenSearch it listed products no page shows (320 against
     * Algolia's 315, 2026-09-26). The product page reads the copies from the database, not from search,
     * so keeping them out of the index leaves the seller comparison as it was.
     *
     * @param int[] $productIds
     * @return int[]
     */
    public function searchableIds(array $productIds): array
    {
        $ids = $this->approvedIds($productIds);
        $selectFrom = $this->attributeId('select_from_product_id');
        if (!$ids || $selectFrom === null) {
            return $ids;
        }

        try {
            $connection = $this->resource->getConnection();
            $copies = $connection->fetchCol(
                $connection->select()
                    ->from($this->resource->getTableName('catalog_product_entity_int'), ['entity_id'])
                    ->where('attribute_id = ?', $selectFrom)
                    ->where('store_id = 0')
                    ->where('value > 0')
                    ->where('entity_id IN (?)', array_map('intval', $ids))
            );
        } catch (\Throwable $e) {
            $this->logger->error('StorefrontVisibility::searchableIds failed, keeping copies: ' . $e->getMessage());

            return $ids;
        }

        $copies = array_flip(array_map('intval', $copies));

        return array_values(array_filter($ids, static fn ($id) => !isset($copies[(int) $id])));
    }

    /**
     * The subset of $productIds a customer could actually open on $storeId.
     *
     * approvedIds() plus enabled status and catalog visibility, both resolved
     * the way the storefront resolves them — the store row when there is one,
     * the default row otherwise. This is the gate for anything that hands the
     * customer a LINK, because a link to a product failing any of these is a
     * link to a 404.
     *
     * @param int[] $productIds
     * @return int[] in the order given
     */
    public function sellableIds(array $productIds, int $storeId): array
    {
        if (!$productIds) {
            return [];
        }

        try {
            $select = $this->baseSelect($productIds);
            if ($select === null) {
                return $productIds;
            }

            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('catalog_product_entity_int');

            $status = $this->attributeId('status');
            $visibility = $this->attributeId('visibility');
            if ($status === null || $visibility === null) {
                return $productIds;
            }

            $select
                ->joinLeft(
                    ['st_d' => $table],
                    "st_d.entity_id = e.entity_id AND st_d.attribute_id = {$status} AND st_d.store_id = 0",
                    []
                )
                ->joinLeft(
                    ['st_s' => $table],
                    "st_s.entity_id = e.entity_id AND st_s.attribute_id = {$status} AND st_s.store_id = {$storeId}",
                    []
                )
                ->joinLeft(
                    ['vi_d' => $table],
                    "vi_d.entity_id = e.entity_id AND vi_d.attribute_id = {$visibility} AND vi_d.store_id = 0",
                    []
                )
                ->joinLeft(
                    ['vi_s' => $table],
                    "vi_s.entity_id = e.entity_id AND vi_s.attribute_id = {$visibility} AND vi_s.store_id = {$storeId}",
                    []
                )
                ->where('COALESCE(st_s.value, st_d.value) = ?', Product\Attribute\Source\Status::STATUS_ENABLED)
                ->where('COALESCE(vi_s.value, vi_d.value) IN (?)', $this->catalogVisibilityIds());

            $allowed = array_map('intval', $connection->fetchCol($select));
        } catch (\Throwable $e) {
            $this->logger->error('StorefrontVisibility::sellableIds failed, passing all ids: ' . $e->getMessage());

            return $productIds;
        }

        $allowed = array_flip($allowed);

        return array_values(array_filter($productIds, static fn ($id) => isset($allowed[(int) $id])));
    }

    /**
     * `SELECT entity_id` restricted to approved products of active vendors.
     *
     * Returns null when the vendor extension's own columns are not there, which
     * is the caller's signal to let everything through.
     */
    private function baseSelect(array $productIds): ?\Magento\Framework\DB\Select
    {
        $connection = $this->resource->getConnection();
        $entity = $this->resource->getTableName('catalog_product_entity');

        $approval = $this->attributeId('approval');
        if ($approval === null) {
            return null;
        }

        $select = $connection->select()
            ->from(['e' => $entity], ['entity_id'])
            ->joinInner(
                ['appr' => $this->resource->getTableName('catalog_product_entity_int')],
                "appr.entity_id = e.entity_id AND appr.attribute_id = {$approval} AND appr.store_id = 0",
                []
            )
            ->where('e.entity_id IN (?)', array_map('intval', $productIds))
            ->where('appr.value = ?', self::STOREFRONT_APPROVAL);

        $inactive = $this->getInactiveVendorIds();
        if ($inactive && $connection->tableColumnExists($entity, 'vendor_id')) {
            $select->where('e.vendor_id NOT IN (?)', $inactive);
        }

        return $select;
    }

    /**
     * Catalog-visible values, as the storefront defines them.
     *
     * @return int[]
     */
    private function catalogVisibilityIds(): array
    {
        return [Visibility::VISIBILITY_IN_CATALOG, Visibility::VISIBILITY_BOTH];
    }

    /**
     * @return int[]
     */
    private function getInactiveVendorIds(): array
    {
        if ($this->inactiveVendorIds === null) {
            try {
                $this->inactiveVendorIds = array_map('intval', $this->vendorHelper->getNotActiveVendorIds());
            } catch (\Throwable $e) {
                $this->inactiveVendorIds = [];
            }
        }

        return $this->inactiveVendorIds;
    }

    private function attributeId(string $code): ?int
    {
        try {
            $id = (int) $this->eavConfig->getAttribute(Product::ENTITY, $code)->getId();
        } catch (\Throwable $e) {
            return null;
        }

        return $id ?: null;
    }
}
