<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppOrders\Model\Package;

use Magento\Framework\App\ResourceConnection;

/**
 * The rows behind the packages of many orders, one query per table whatever the number of orders
 * (a customer's order list asks for every order of the page at once).
 *
 * Vnecoms_VendorsSales keeps a store's part of an order in ves_vendor_sales_order (one row per
 * seller, created when the order is placed: Observer\ProcessOrder) and tags the core rows with it:
 * sales_order_item.vendor_id / vendor_order_id, sales_shipment.vendor_order_id and
 * sales_order_status_history.vendor_id / vendor_order_status (the vendor order id, despite the
 * name: Controller\Vendors\Order\AddComment). Read only; Vnecoms' own getAllItems() back-fills a
 * missing vendor_order_id by saving the item, which a read must never do (PackageBuilder applies the
 * same fallback in memory).
 */
class PackageReader
{
    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * @param int[] $orderIds sales_order.entity_id
     * @return array{
     *     vendor_orders: list<array<string, mixed>>,
     *     items: list<array<string, mixed>>,
     *     shipments: list<array<string, mixed>>,
     *     shipment_items: list<array<string, mixed>>,
     *     tracks: list<array<string, mixed>>,
     *     comments: list<array<string, mixed>>
     * }
     */
    public function read(array $orderIds): array
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));
        $empty = [
            'vendor_orders' => [],
            'items' => [],
            'shipments' => [],
            'shipment_items' => [],
            'tracks' => [],
            'comments' => [],
        ];
        if (!$orderIds) {
            return $empty;
        }
        $connection = $this->resource->getConnection();

        $vendorOrders = $connection->fetchAll(
            $connection->select()
                ->from($this->resource->getTableName('ves_vendor_sales_order'), [
                    'entity_id', 'vendor_id', 'order_id', 'state', 'status',
                    'subtotal', 'discount_amount', 'tax_amount', 'shipping_amount', 'grand_total',
                    'shipping_method', 'shipping_description',
                ])
                ->where('order_id IN (?)', $orderIds)
                ->order('entity_id ASC')
        );

        $items = $connection->fetchAll(
            $connection->select()
                ->from($this->resource->getTableName('sales_order_item'), [
                    'item_id', 'order_id', 'parent_item_id', 'vendor_id', 'vendor_order_id', 'product_options',
                    'row_total', 'tax_amount', 'discount_amount', 'discount_tax_compensation_amount',
                ])
                ->where('order_id IN (?)', $orderIds)
                ->order('item_id ASC')
        );

        $shipments = $connection->fetchAll(
            $connection->select()
                ->from($this->resource->getTableName('sales_shipment'), [
                    'entity_id', 'order_id', 'increment_id', 'created_at', 'vendor_order_id',
                ])
                ->where('order_id IN (?)', $orderIds)
                ->order('entity_id ASC')
        );

        //  A shipment made from the order itself in admin carries no vendor order: its lines say
        //  whose it is. Only those shipments' lines are read.
        $vendorOrderOf = [];
        foreach ($vendorOrders as $row) {
            $vendorOrderOf[(int) $row['entity_id']] = (int) $row['order_id'];
        }
        $unowned = [];
        foreach ($shipments as $row) {
            $vendorOrderId = (int) $row['vendor_order_id'];
            if (($vendorOrderOf[$vendorOrderId] ?? null) !== (int) $row['order_id']) {
                $unowned[] = (int) $row['entity_id'];
            }
        }
        $shipmentItems = $unowned
            ? $connection->fetchAll(
                $connection->select()
                    ->from($this->resource->getTableName('sales_shipment_item'), ['parent_id', 'order_item_id'])
                    ->where('parent_id IN (?)', $unowned)
                    ->order('entity_id ASC')
            )
            : [];

        $tracks = $shipments
            ? $connection->fetchAll(
                $connection->select()
                    ->from($this->resource->getTableName('sales_shipment_track'), [
                        'entity_id', 'parent_id', 'order_id', 'track_number', 'title', 'carrier_code',
                    ])
                    ->where('order_id IN (?)', $orderIds)
                    ->order('entity_id ASC')
            )
            : [];

        //  What the website shows under "About Your Order" (Order::getVisibleStatusHistory()):
        //  visible on the storefront and not empty. Only the rows a seller wrote on its vendor order.
        $comments = $vendorOrderOf
            ? $connection->fetchAll(
                $connection->select()
                    ->from($this->resource->getTableName('sales_order_status_history'), [
                        'entity_id', 'parent_id', 'vendor_id', 'vendor_order_status', 'comment', 'created_at',
                    ])
                    ->where('parent_id IN (?)', $orderIds)
                    ->where('vendor_order_status IN (?)', array_keys($vendorOrderOf))
                    ->where('is_visible_on_front = ?', 1)
                    ->where('comment IS NOT NULL')
                    ->where("comment <> ''")
                    ->order(['created_at ASC', 'entity_id ASC'])
            )
            : [];

        return [
            'vendor_orders' => $vendorOrders,
            'items' => $items,
            'shipments' => $shipments,
            'shipment_items' => $shipmentItems,
            'tracks' => $tracks,
            'comments' => $comments,
        ];
    }
}
