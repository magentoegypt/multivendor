<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\FulfillmentTracking\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\Data\OrderInterface;
use MagentoEgypt\Fulfillment\Model\Dispatch;
use MagentoEgypt\HubApp\Api\SellerSummaryProviderInterface;
use MagentoEgypt\HubAppOrders\Model\Package\TrackingUrl;
use Psr\Log\LoggerInterface;

/**
 * An order's deliveries as its customer may see them (CL036-DEV17).
 *
 * The groups come from MagentoEgypt_Fulfillment's Dispatch::describe(), the same snapshot the
 * seller order page, the admin workspace and the fleet API work from: one group per seller for
 * orders placed before the module, one per checkout leg (direct, into the warehouse, out of it)
 * for allocated orders. Progress recorded there is what moves a delivery along.
 *
 * Kept OUT of the customer's view, as Preview::publicPlan() does for the checkout plan: source
 * codes and warehouse addresses, rates and costs, stock, the delivery address, internal notes
 * (an issue shows only as "delayed"), staff actions and versions.
 *
 * Every database read is one query for the whole batch of orders (a page of customer.orders),
 * not one per order.
 */
class DeliveryProjection
{
    public function __construct(
        private readonly Dispatch $dispatch,
        private readonly ResourceConnection $resource,
        private readonly StatusMap $map,
        private readonly SellerSummaryProviderInterface $sellers,
        private readonly TrackingUrl $trackingUrl,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Projections keyed by order entity id. An order whose projection fails is logged and left
     * out, so one bad order never takes a whole list down.
     *
     * Labels are not filled in here; see Labels::apply().
     *
     * @param OrderInterface[] $orders
     * @return array<int, array<string, mixed>>
     */
    public function forOrders(array $orders, int $storeId): array
    {
        $ids = [];
        foreach ($orders as $order) {
            $ids[] = (int) $order->getEntityId();
        }
        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) {
            return [];
        }

        $events = $this->events($ids);
        $vendorOrders = $this->vendorOrders($ids);
        [$shippedAt, $tracks] = $this->shipments($ids);

        $built = [];
        $vendorIds = [];
        foreach ($orders as $order) {
            $id = (int) $order->getEntityId();
            try {
                $built[$id] = $this->build($order, $events[$id] ?? [], $vendorOrders[$id] ?? [], $shippedAt, $tracks);
                foreach ($built[$id]['deliveries'] as $delivery) {
                    if (count($delivery['vendor_ids']) === 1) {
                        $vendorIds[$delivery['vendor_ids'][0]] = $delivery['vendor_ids'][0];
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->error(
                    'FulfillmentTracking: order ' . $id . ' could not be projected: ' . $e->getMessage(),
                    ['exception' => $e]
                );
            }
        }

        $summaries = $vendorIds ? $this->sellers->getByVendorIds(array_values($vendorIds), $storeId) : [];
        $marketplace = null;
        foreach ($built as &$fulfillment) {
            foreach ($fulfillment['deliveries'] as &$delivery) {
                if (count($delivery['vendor_ids']) === 1) {
                    //  Absent for a seller no longer approved: the field is then null.
                    $delivery['seller'] = $summaries[$delivery['vendor_ids'][0]] ?? null;
                } else {
                    //  A consolidated leg out of the warehouse carries several sellers' items.
                    $marketplace ??= $this->sellers->getMarketplace($storeId);
                    $delivery['seller'] = $marketplace;
                }
                unset($delivery['vendor_ids']);
            }
            unset($delivery);
        }
        unset($fulfillment);

        return $built;
    }

    /**
     * @param array<string, list<array{state: string, at: string}>> $events by group id
     * @param array<int, array{state: string, updated_at: ?string}> $vendorOrders by vendor id
     * @param array<int, string> $shippedAt order item id => first shipment time
     * @param array<int, array{carrier_code: string, title: string, number: string}> $tracks order item id => latest track
     * @return array<string, mixed>
     */
    private function build(OrderInterface $order, array $events, array $vendorOrders, array $shippedAt, array $tracks): array
    {
        $orderState = (string) $order->getState();
        $forced = $this->map->forOrderState($orderState);
        $placedAt = $this->iso($order->getCreatedAt());
        $changedAt = $this->iso($order->getUpdatedAt());

        //  Top-level physical lines, the way Dispatch groups them, keyed vendor|sku.
        $lines = [];
        foreach ($order->getAllItems() as $item) {
            if ($item->getParentItemId() || $item->getIsVirtual()) {
                continue;
            }
            $open = (float) $item->getQtyOrdered() - (float) $item->getQtyCanceled() - (float) $item->getQtyRefunded();
            $lines[(int) $item->getData('vendor_id') . '|' . $item->getSku()][] = [
                'id' => (int) $item->getItemId(),
                'shipped' => $open <= 0 || (float) $item->getQtyShipped() + 0.0001 >= $open,
            ];
        }

        $deliveries = [];
        foreach ($this->dispatch->describe($order)['groups'] as $index => $group) {
            $leg = (string) $group['leg'];
            $itemIds = [];
            $allShipped = true;
            foreach ($group['items'] as $entry) {
                $matches = $lines[(int) $entry['vendor_id'] . '|' . $entry['sku']] ?? [];
                if (!$matches) {
                    $allShipped = false;
                }
                foreach ($matches as $line) {
                    $itemIds[$line['id']] = $line['id'];
                    $allShipped = $allShipped && $line['shipped'];
                }
            }
            $itemIds = array_values($itemIds);
            $allShipped = $allShipped && $itemIds !== [];

            $vendorIds = array_values(array_map('intval', $group['vendor_ids']));
            $vendorOrder = count($vendorIds) === 1 ? ($vendorOrders[$vendorIds[0]] ?? null) : null;
            $untracked = $this->map->untracked($orderState, $vendorOrder['state'] ?? null, $allShipped);

            [$status, $tracked] = $this->map->forGroup(
                $leg,
                (string) ($group['state'] ?? ''),
                (int) ($group['version'] ?? 0),
                (string) ($group['source_state'] ?? ''),
                $untracked
            );
            if ($forced !== null) {
                $status = $forced;
            }

            $handledBy = match ($group['responsibility'] ?? null) {
                'vendor' => 'seller',
                'marketplace' => 'hub_market',
                default => null,
            };

            //  Timeline: placed, then every recorded move, then what the order itself says.
            $timeline = [['status_code' => StatusMap::ORDERED, 'at' => $placedAt]];
            $push = static function (string $code, ?string $at) use (&$timeline): void {
                $last = end($timeline);
                if ($last['status_code'] === $code) {
                    return;
                }
                //  Times come from different tables (events, shipments, vendor orders) and can be a
                //  second apart the wrong way; a step never reads as earlier than the one before it.
                if ($at !== null && $last['at'] !== null && $at < $last['at']) {
                    $at = $last['at'];
                }
                $timeline[] = ['status_code' => $code, 'at' => $at];
            };
            foreach ($events[$group['group_id']] ?? [] as $event) {
                $code = $this->map->forRecorded($event['state'], $leg);
                //  "Preparing" straight after "placed" is the team classifying the order, not news;
                //  after a delay it is the delivery resuming, and it is shown.
                if ($code === null || ($code === StatusMap::PREPARING && end($timeline)['status_code'] === StatusMap::ORDERED)) {
                    continue;
                }
                $push($code, $event['at']);
            }
            $firstShipped = null;
            foreach ($itemIds as $itemId) {
                if (isset($shippedAt[$itemId]) && ($firstShipped === null || $shippedAt[$itemId] < $firstShipped)) {
                    $firstShipped = $shippedAt[$itemId];
                }
            }
            if (!$tracked) {
                if ($allShipped && $firstShipped !== null) {
                    $push(StatusMap::SHIPPED, $firstShipped);
                }
                if (in_array($status, [StatusMap::COMPLETED, StatusMap::CANCELED, StatusMap::REFUNDED, StatusMap::ON_HOLD], true)) {
                    $push($status, $this->iso($vendorOrder['updated_at'] ?? null) ?? $changedAt);
                }
            }
            if ($forced !== null && $forced !== StatusMap::ORDERED) {
                $push($forced, $changedAt);
            }

            $deliveries[] = [
                //  Position, not the internal group id: that names sources and rates.
                'id' => 'delivery-' . ($index + 1),
                'route' => $this->map->route($leg),
                'vendor_ids' => $vendorIds,
                'seller' => null,
                'handled_by' => $handledBy,
                'status_code' => $status,
                'tracked' => $tracked,
                'courier' => $this->courier($group, $tracked, $handledBy, $itemIds, $tracks),
                'expected_delivery' => $tracked ? $this->iso($group['expected_delivery'] ?? null) : null,
                'updated_at' => $tracked ? $this->iso($group['updated_at'] ?? null) : (end($timeline)['at'] ?? null),
                'item_uids' => array_map(static fn (int $id): string => base64_encode((string) $id), $itemIds),
                'timeline' => $timeline,
            ];
        }

        return [
            'status_code' => $this->map->overall(
                $orderState,
                array_map(static fn (array $d): array => ['route' => $d['route'], 'status_code' => $d['status_code']], $deliveries)
            ),
            'deliveries' => $deliveries,
        ];
    }

    /**
     * Who carries the delivery: what its team recorded, else the latest Magento tracking number on
     * its lines (the one the website's order page shows), else null.
     *
     * @param array<string, mixed> $group
     * @param int[] $itemIds
     * @param array<int, array{carrier_code: string, title: string, number: string, id: int}> $tracks
     * @return array<string, mixed>|null
     */
    private function courier(array $group, bool $tracked, ?string $handledBy, array $itemIds, array $tracks): ?array
    {
        $execution = $group['execution'] ?? null;
        if ($tracked && in_array($execution, ['own', 'third_party'], true)) {
            $carrier = $execution === 'third_party' ? trim((string) ($group['carrier'] ?? '')) : '';
            $number = $execution === 'third_party' ? trim((string) ($group['tracking'] ?? '')) : '';
            return [
                'type' => $execution,
                'name' => $carrier !== '' ? $carrier : null,
                'tracking_number' => $number !== '' ? $number : null,
                'tracking_url' => $carrier !== '' && $number !== '' ? $this->trackingUrl->forTrack($carrier, $number) : null,
            ];
        }

        $latest = null;
        foreach ($itemIds as $itemId) {
            $track = $tracks[$itemId] ?? null;
            if ($track !== null && ($latest === null || $track['id'] > $latest['id'])) {
                $latest = $track;
            }
        }
        if ($latest === null) {
            return null;
        }
        $name = trim($latest['title']) !== '' ? trim($latest['title']) : trim($latest['carrier_code']);

        return [
            'type' => 'third_party',
            'name' => $name !== '' ? $name : null,
            'tracking_number' => $latest['number'] !== '' ? $latest['number'] : null,
            'tracking_url' => $latest['number'] !== '' ? $this->trackingUrl->forTrack($latest['carrier_code'], $latest['number']) : null,
        ];
    }

    /**
     * Recorded progress by order and group: Dispatch updates (snapshot state) and warehouse
     * Workflow moves (group_progress), oldest first.
     *
     * @param int[] $orderIds
     * @return array<int, array<string, list<array{state: string, at: ?string}>>>
     */
    private function events(array $orderIds): array
    {
        $db = $this->resource->getConnection();
        $table = $this->resource->getTableName('me_fulfillment_event');
        $rows = $db->fetchAll(
            $db->select()
                ->from($table, ['order_id', 'kind', 'payload_json', 'created_at'])
                ->where('order_id IN (?)', $orderIds)
                ->where('kind IN (?)', ['dispatch_update', 'group_progress'])
                ->order('event_id ASC')
        );

        $result = [];
        foreach ($rows as $row) {
            $payload = json_decode((string) $row['payload_json'], true, 64, JSON_THROW_ON_ERROR);
            $source = $row['kind'] === 'dispatch_update' ? ($payload['snapshot'] ?? []) : $payload;
            $groupId = $source['group_id'] ?? null;
            $state = $source['state'] ?? null;
            if (!is_string($groupId) || !is_string($state)) {
                continue;
            }
            $result[(int) $row['order_id']][$groupId][] = ['state' => $state, 'at' => $this->iso($row['created_at'])];
        }

        return $result;
    }

    /**
     * Vnecoms vendor orders: their state is what the website shows a seller's part of an order as.
     *
     * @param int[] $orderIds
     * @return array<int, array<int, array{state: string, updated_at: ?string}>>
     */
    private function vendorOrders(array $orderIds): array
    {
        $db = $this->resource->getConnection();
        $rows = $db->fetchAll(
            $db->select()
                ->from($this->resource->getTableName('ves_vendor_sales_order'), ['order_id', 'vendor_id', 'state', 'updated_at'])
                ->where('order_id IN (?)', $orderIds)
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['order_id']][(int) $row['vendor_id']] = [
                'state' => (string) $row['state'],
                'updated_at' => $row['updated_at'],
            ];
        }

        return $result;
    }

    /**
     * Magento shipments of the orders' lines: when each line first shipped, and the latest
     * tracking number on a shipment that carries it.
     *
     * @param int[] $orderIds
     * @return array{0: array<int, string>, 1: array<int, array{id: int, carrier_code: string, title: string, number: string}>}
     */
    private function shipments(array $orderIds): array
    {
        $db = $this->resource->getConnection();
        $rows = $db->fetchAll(
            $db->select()
                ->from(['s' => $this->resource->getTableName('sales_shipment')], ['shipment_id' => 'entity_id', 'created_at'])
                ->join(['si' => $this->resource->getTableName('sales_shipment_item')], 'si.parent_id = s.entity_id', ['order_item_id'])
                ->where('s.order_id IN (?)', $orderIds)
        );

        $shippedAt = [];
        $itemsByShipment = [];
        foreach ($rows as $row) {
            $itemId = (int) $row['order_item_id'];
            $at = $this->iso($row['created_at']);
            if ($at !== null && (!isset($shippedAt[$itemId]) || $at < $shippedAt[$itemId])) {
                $shippedAt[$itemId] = $at;
            }
            $itemsByShipment[(int) $row['shipment_id']][] = $itemId;
        }

        $tracks = [];
        if ($itemsByShipment) {
            $trackRows = $db->fetchAll(
                $db->select()
                    ->from($this->resource->getTableName('sales_shipment_track'), ['entity_id', 'parent_id', 'carrier_code', 'title', 'track_number'])
                    ->where('order_id IN (?)', $orderIds)
                    ->order('entity_id ASC')
            );
            foreach ($trackRows as $row) {
                foreach ($itemsByShipment[(int) $row['parent_id']] ?? [] as $itemId) {
                    $tracks[$itemId] = [
                        'id' => (int) $row['entity_id'],
                        'carrier_code' => (string) $row['carrier_code'],
                        'title' => (string) $row['title'],
                        'number' => trim((string) $row['track_number']),
                    ];
                }
            }
        }

        return [$shippedAt, $tracks];
    }

    /**
     * A stored UTC time ("Y-m-d H:i:s" or ISO with offset) as ISO-8601 UTC, or null.
     */
    private function iso(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        try {
            $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        } catch (\Exception) {
            return null;
        }

        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
