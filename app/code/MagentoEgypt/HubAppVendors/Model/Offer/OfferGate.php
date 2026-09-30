<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Model\Offer;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use MagentoEgypt\VendorExtend\Model\StorefrontVisibility;
use Psr\Log\LoggerInterface;

/**
 * Which family members the website's price comparison would list as an offer.
 *
 * The website's list is Vnecoms LoadProduct::getProductComparison() (the
 * "Sold by N other sellers" block of the product page):
 *   - approved (the storefront narrows the allowed approvals to APPROVED), owned
 *     by an active seller, enabled and visible in the catalog of the store view:
 *     StorefrontVisibility::sellableIds(), the gate hm_seller and every app list
 *     use;
 *   - owned by a seller: products of Hub Market itself (vendor 0) are skipped;
 *   - Process::checkProductSalesEnable(): a non-configurable offer must also be
 *     visible in search (with catalog visibility that leaves "Catalog, Search");
 *     a configurable one is judged by its children and its stock;
 *   - in stock, and the seller approved: checked after this gate, with the
 *     product data and the seller summaries (OfferFinder).
 * One query besides the storefront gate's. Fails closed: an offer that cannot
 * be checked is not listed.
 */
class OfferGate
{
    public const TYPE_CONFIGURABLE = 'configurable';

    public function __construct(
        private readonly StorefrontVisibility $visibility,
        private readonly ResourceConnection $resource,
        private readonly EavConfig $eavConfig,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param int[] $productIds
     * @return array<int, array{vendor_id: int, type_id: string}> the products that pass, by id
     */
    public function passing(array $productIds, int $storeId): array
    {
        $ids = FamilyReader::normalise($productIds);
        if (!$ids) {
            return [];
        }
        $sellable = $this->visibility->sellableIds($ids, $storeId);
        if (!$sellable) {
            return [];
        }

        try {
            $visibilityId = (int) $this->eavConfig->getAttribute(Product::ENTITY, 'visibility')->getId();
            $connection = $this->resource->getConnection();
            $int = $this->resource->getTableName('catalog_product_entity_int');
            $rows = $connection->fetchAll(
                $connection->select()
                    ->from(['e' => $this->resource->getTableName('catalog_product_entity')], ['entity_id', 'vendor_id', 'type_id'])
                    ->joinLeft(
                        ['vd' => $int],
                        "vd.entity_id = e.entity_id AND vd.attribute_id = {$visibilityId} AND vd.store_id = 0",
                        []
                    )
                    ->joinLeft(
                        ['vs' => $int],
                        $connection->quoteInto(
                            "vs.entity_id = e.entity_id AND vs.attribute_id = {$visibilityId} AND vs.store_id = ?",
                            $storeId
                        ),
                        ['visibility' => new Expression('COALESCE(vs.value, vd.value)')]
                    )
                    ->where('e.entity_id IN (?)', array_map('intval', $sellable))
            );
        } catch (\Throwable $e) {
            $this->logger->warning('HubApp: other sellers\' offers unavailable: ' . $e->getMessage());

            return [];
        }

        $out = [];
        foreach ($rows as $row) {
            $vendorId = (int) ($row['vendor_id'] ?? 0);
            $typeId = (string) ($row['type_id'] ?? '');
            $visibility = isset($row['visibility']) ? (int) $row['visibility'] : null;
            if (self::offerable($vendorId, $typeId, $visibility)) {
                $out[(int) $row['entity_id']] = ['vendor_id' => $vendorId, 'type_id' => $typeId];
            }
        }

        return $out;
    }

    /**
     * The website's per-offer rule, after the storefront gate. Pure; unit-tested.
     */
    public static function offerable(int $vendorId, string $typeId, ?int $visibility): bool
    {
        if ($vendorId <= 0) {
            return false;
        }
        if ($typeId === self::TYPE_CONFIGURABLE || !$visibility) {
            return true;
        }

        return in_array($visibility, [Visibility::VISIBILITY_IN_SEARCH, Visibility::VISIBILITY_BOTH], true);
    }
}
