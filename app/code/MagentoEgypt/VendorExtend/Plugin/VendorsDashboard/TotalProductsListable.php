<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\VendorsDashboard;

use Magento\Framework\App\ResourceConnection;

/**
 * Seller "Total Products": the seller's products, not counting configurable variants that the
 * catalog never lists on their own.
 *
 * Vnecoms counts every catalog_product_entity row with the seller's vendor_id. A configurable
 * product's colour/size variants are rows too, set "Not Visible Individually", so seller V8S2 saw
 * 15 (now 16) against the 13 on its storefront page: 7888-1 plus its three variants counted as
 * four (TC66-QA01, 2026-09-28). Pending and disabled products still count, because the seller
 * manages them. Only variants that are both linked to a parent and not visible individually are
 * left out.
 *
 * One plugin for the three places that print it: the seller panel dashboard block, the admin
 * vendor-edit dashboard tab (both read the vendor from the block), and the vendor app's dashboard
 * API (takes the vendor id).
 */
class TotalProductsListable
{
    public function __construct(private readonly ResourceConnection $resource)
    {
    }

    public function aroundGetTotalProducts($subject, callable $proceed, $vendorId = null)
    {
        if ($vendorId === null && method_exists($subject, 'getVendor') && $subject->getVendor()) {
            $vendorId = $subject->getVendor()->getId();
        }
        if (!$vendorId) {
            return $vendorId === null ? $proceed() : $proceed($vendorId);
        }

        try {
            return (int) $this->count((int) $vendorId);
        } catch (\Throwable $e) {
            return $vendorId === null ? $proceed() : $proceed($vendorId);
        }
    }

    private function count(int $vendorId): string
    {
        $connection = $this->resource->getConnection();
        $entity = $this->resource->getTableName('catalog_product_entity');
        $visibilityId = (int) $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('eav_attribute'), ['attribute_id'])
                ->where('attribute_code = ?', 'visibility')
                ->where('entity_type_id = ?', 4)
        );

        $variant = $connection->select()
            ->from(['r' => $this->resource->getTableName('catalog_product_relation')], [new \Zend_Db_Expr('1')])
            ->join(
                ['vis' => $this->resource->getTableName('catalog_product_entity_int')],
                'vis.entity_id = r.child_id AND vis.store_id = 0 AND vis.attribute_id = ' . $visibilityId,
                []
            )
            ->where('r.child_id = e.entity_id')
            ->where('vis.value = ?', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE);

        return (string) $connection->fetchOne(
            $connection->select()
                ->from(['e' => $entity], [new \Zend_Db_Expr('COUNT(e.entity_id)')])
                ->where('e.vendor_id = ?', $vendorId)
                ->where('NOT EXISTS (' . $variant . ')')
        );
    }
}
