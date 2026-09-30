<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppOrders\Model\Package;

/**
 * Turns PackageReader rows into HmOrderPackage values, order by order. No I/O: the seller summary
 * and the status label are added afterwards (PackageService), for every order of a response at once.
 *
 * THE SPLIT IS VNECOMS'. When an order is placed, Vnecoms_VendorsSales (Observer\ProcessOrder) groups
 * its lines by sales_order_item.vendor_id and writes one vendor order per seller that still exists;
 * lines of vendor 0 (products created in admin, sold by Hub Market itself) get none. So:
 *   - a line belongs to the vendor order named by its vendor_order_id, else to its seller's vendor
 *     order (Vnecoms\VendorsSales\Model\Order::getAllItems() applies the same fallback);
 *   - lines left over form a package per seller id, Hub Market's own lines above all. Such a package
 *     has the order's own status, and its totals are summed from its lines with ProcessOrder's
 *     formula (children of a bundle priced per child, parents otherwise; no delivery);
 *   - packages come in the order their stores first appear among the lines;
 *   - child lines (a configurable's simple, a bundle's options) go with their parent and are never
 *     listed: item_uids are the top-level lines, as CustomerOrder.items lists them.
 *
 * DELIVERY. A vendor order holds a delivery charge only when the order paid delivery per store
 * (Vnecoms_VendorsShipping's vendor_multirate carrier writes the store's own method into
 * shipping_method). Otherwise one charge covered the whole order and the vendor order holds 0, which
 * is not "free delivery": shipping_amount is then null.
 *
 * SHIPMENTS belong to the vendor order they were made from (sales_shipment.vendor_order_id). One made
 * from the order itself in admin names no vendor order, so its lines say whose it is; a shipment
 * holding lines of two stores is listed under both.
 */
class PackageBuilder
{
    /** \Magento\Catalog\Model\Product\Type\AbstractType::CALCULATE_CHILD */
    private const CALCULATE_CHILD = 0;

    /** Below this an amount is zero (amounts carry four decimals). */
    private const EPSILON = 0.00005;

    public function __construct(
        private readonly TrackingUrl $trackingUrl
    ) {
    }

    /**
     * @param array<int, array{status: string, state: string, currency: string}> $orders order id => the
     *        order's own status, state and currency code
     * @param array<string, list<array<string, mixed>>> $rows PackageReader::read()
     * @return array<int, list<array<string, mixed>>> order id => packages, each with vendor_id (whose
     *         summary PackageService adds as seller) and status_code (which it labels)
     */
    public function build(array $orders, array $rows): array
    {
        $vendorOrders = [];
        foreach ($rows['vendor_orders'] ?? [] as $row) {
            $vendorOrders[(int) $row['order_id']][(int) $row['entity_id']] = $row;
        }
        $items = [];
        foreach ($rows['items'] ?? [] as $row) {
            $items[(int) $row['order_id']][] = $row;
        }
        $shipments = [];
        foreach ($rows['shipments'] ?? [] as $row) {
            $shipments[(int) $row['order_id']][] = $row;
        }
        $shipmentItems = [];
        foreach ($rows['shipment_items'] ?? [] as $row) {
            $shipmentItems[(int) $row['parent_id']][] = (int) $row['order_item_id'];
        }
        $tracks = [];
        foreach ($rows['tracks'] ?? [] as $row) {
            $tracks[(int) $row['parent_id']][] = $row;
        }
        $comments = [];
        foreach ($rows['comments'] ?? [] as $row) {
            $comments[(int) $row['parent_id']][] = $row;
        }

        $out = [];
        foreach ($orders as $orderId => $order) {
            $out[(int) $orderId] = $this->packagesOf(
                $order,
                $vendorOrders[(int) $orderId] ?? [],
                $items[(int) $orderId] ?? [],
                $shipments[(int) $orderId] ?? [],
                $shipmentItems,
                $tracks,
                $comments[(int) $orderId] ?? []
            );
        }

        return $out;
    }

    /**
     * @param array{status: string, state: string, currency: string} $order
     * @param array<int, array<string, mixed>> $vendorOrders vendor order id => row
     * @param list<array<string, mixed>> $items
     * @param list<array<string, mixed>> $shipments
     * @param array<int, int[]> $shipmentItems shipment id => order item ids
     * @param array<int, list<array<string, mixed>>> $tracks shipment id => rows
     * @param list<array<string, mixed>> $comments
     * @return list<array<string, mixed>>
     */
    private function packagesOf(
        array $order,
        array $vendorOrders,
        array $items,
        array $shipments,
        array $shipmentItems,
        array $tracks,
        array $comments
    ): array {
        $currency = (string) ($order['currency'] ?? '');

        $vendorOrderOfSeller = [];
        foreach ($vendorOrders as $vendorOrderId => $row) {
            $vendorOrderOfSeller[(int) $row['vendor_id']] ??= $vendorOrderId;
        }

        //  Top-level lines first, in line order: the packages open in that order.
        /** @var array<string, array{vendor_order_id: int, vendor_id: int, lines: list<array>}> $packages */
        $packages = [];
        $packageOfItem = [];
        $children = [];
        foreach ($items as $item) {
            if ((int) ($item['parent_item_id'] ?? 0) > 0) {
                $children[(int) $item['parent_item_id']][] = $item;
                continue;
            }
            $vendorOrderId = (int) ($item['vendor_order_id'] ?? 0);
            $vendorId = (int) ($item['vendor_id'] ?? 0);
            if (!isset($vendorOrders[$vendorOrderId])) {
                $vendorOrderId = $vendorId > 0 ? ($vendorOrderOfSeller[$vendorId] ?? 0) : 0;
            }
            $key = $vendorOrderId > 0 ? 'vendor_order:' . $vendorOrderId : 'seller:' . $vendorId;
            $packages[$key] ??= [
                'vendor_order_id' => $vendorOrderId,
                'vendor_id' => $vendorOrderId > 0 ? (int) $vendorOrders[$vendorOrderId]['vendor_id'] : $vendorId,
                'lines' => [],
            ];
            $packages[$key]['lines'][] = $item;
            $packageOfItem[(int) $item['item_id']] = $key;
        }
        //  A child line goes with its parent (a shipment may name it rather than the parent).
        foreach ($children as $parentId => $childLines) {
            foreach ($childLines as $child) {
                if (isset($packageOfItem[$parentId])) {
                    $packageOfItem[(int) $child['item_id']] = $packageOfItem[$parentId];
                }
            }
        }

        $shipmentsOf = [];
        foreach ($shipments as $shipment) {
            $keys = [];
            $vendorOrderKey = 'vendor_order:' . (int) ($shipment['vendor_order_id'] ?? 0);
            if (isset($packages[$vendorOrderKey])) {
                $keys[] = $vendorOrderKey;
            } else {
                foreach ($shipmentItems[(int) $shipment['entity_id']] ?? [] as $orderItemId) {
                    $key = $packageOfItem[$orderItemId] ?? null;
                    if ($key !== null && !in_array($key, $keys, true)) {
                        $keys[] = $key;
                    }
                }
                if (!$keys && count($packages) === 1) {
                    $keys[] = (string) array_key_first($packages);
                }
            }
            foreach ($keys as $key) {
                $shipmentsOf[$key][] = $this->shipment($shipment, $tracks[(int) $shipment['entity_id']] ?? []);
            }
        }

        $commentsOf = [];
        foreach ($comments as $comment) {
            $vendorOrderId = (int) ($comment['vendor_order_status'] ?? 0);
            $vendorOrder = $vendorOrders[$vendorOrderId] ?? null;
            $key = 'vendor_order:' . $vendorOrderId;
            //  Vnecoms reads a vendor order's history by both columns (Model\Order::getStatusHistoryCollection).
            if ($vendorOrder === null || !isset($packages[$key])
                || (int) ($comment['vendor_id'] ?? 0) !== (int) $vendorOrder['vendor_id']) {
                continue;
            }
            $message = self::plainText((string) ($comment['comment'] ?? ''));
            if ($message === '') {
                continue;
            }
            $commentsOf[$key][] = ['message' => $message, 'created_at' => self::utc($comment['created_at'] ?? '')];
        }

        $out = [];
        foreach ($packages as $key => $package) {
            $vendorOrder = $package['vendor_order_id'] > 0 ? $vendorOrders[$package['vendor_order_id']] : null;
            $out[] = [
                'vendor_id' => $package['vendor_id'],
                'item_uids' => array_map(
                    static fn (array $line): string => base64_encode((string) (int) $line['item_id']),
                    $package['lines']
                ),
            ]
                + ($vendorOrder !== null
                    ? $this->vendorOrderPart($vendorOrder, $order, $currency)
                    : $this->ownLinesPart($package['lines'], $children, $order, $currency))
                + [
                    'shipments' => $shipmentsOf[$key] ?? [],
                    'comments' => $commentsOf[$key] ?? [],
                ];
        }

        return $out;
    }

    /**
     * Status and totals as the vendor order holds them.
     *
     * @param array<string, mixed> $vendorOrder
     * @param array{status: string, state: string, currency: string} $order
     * @return array<string, mixed>
     */
    private function vendorOrderPart(array $vendorOrder, array $order, string $currency): array
    {
        $status = trim((string) ($vendorOrder['status'] ?? ''));
        $state = trim((string) ($vendorOrder['state'] ?? ''));
        $perStore = trim((string) ($vendorOrder['shipping_method'] ?? '')) !== '';
        $method = trim((string) ($vendorOrder['shipping_description'] ?? ''));

        return [
            'status_code' => $status !== '' ? $status : (string) ($order['status'] ?? ''),
            'state' => $state !== '' ? $state : (string) ($order['state'] ?? ''),
            'subtotal' => self::money($vendorOrder['subtotal'] ?? 0, $currency),
            'discount' => self::moneyOrNull(abs((float) ($vendorOrder['discount_amount'] ?? 0)), $currency),
            'tax' => self::moneyOrNull((float) ($vendorOrder['tax_amount'] ?? 0), $currency),
            'shipping_amount' => $perStore ? self::money($vendorOrder['shipping_amount'] ?? 0, $currency) : null,
            'shipping_method' => $perStore && $method !== '' ? $method : null,
            'grand_total' => self::money($vendorOrder['grand_total'] ?? 0, $currency),
        ];
    }

    /**
     * Lines no vendor order holds (Hub Market's own): the order's status, and totals summed the way
     * Vnecoms\VendorsSales\Observer\ProcessOrder sums a vendor order, without delivery.
     *
     * @param list<array<string, mixed>> $lines top-level lines
     * @param array<int, list<array<string, mixed>>> $children parent item id => child lines
     * @param array{status: string, state: string, currency: string} $order
     * @return array<string, mixed>
     */
    private function ownLinesPart(array $lines, array $children, array $order, string $currency): array
    {
        $subtotal = 0.0;
        $tax = 0.0;
        $discount = 0.0;
        $compensation = 0.0;
        foreach ($lines as $line) {
            $childLines = $children[(int) $line['item_id']] ?? [];
            $summed = $childLines && self::childrenCalculated($line) ? $childLines : [$line];
            foreach ($summed as $row) {
                $subtotal += (float) ($row['row_total'] ?? 0);
                $tax += (float) ($row['tax_amount'] ?? 0);
                $discount += (float) ($row['discount_amount'] ?? 0);
                $compensation += (float) ($row['discount_tax_compensation_amount'] ?? 0);
            }
        }

        return [
            'status_code' => (string) ($order['status'] ?? ''),
            'state' => (string) ($order['state'] ?? ''),
            'subtotal' => self::money($subtotal, $currency),
            'discount' => self::moneyOrNull(abs($discount), $currency),
            'tax' => self::moneyOrNull($tax, $currency),
            'shipping_amount' => null,
            'shipping_method' => null,
            'grand_total' => self::money($subtotal + $tax - abs($discount) + $compensation, $currency),
        ];
    }

    /**
     * @param array<string, mixed> $shipment
     * @param list<array<string, mixed>> $tracks
     * @return array<string, mixed>
     */
    private function shipment(array $shipment, array $tracks): array
    {
        $number = (string) ($shipment['increment_id'] ?? '');
        $out = [];
        foreach ($tracks as $track) {
            $trackNumber = trim((string) ($track['track_number'] ?? ''));
            if ($trackNumber === '') {
                continue;
            }
            $code = trim((string) ($track['carrier_code'] ?? ''));
            $title = trim((string) ($track['title'] ?? ''));
            $out[] = [
                'carrier_code' => $code !== '' ? $code : 'custom',
                'carrier_title' => $title !== '' ? $title : $code,
                'number' => $trackNumber,
                'tracking_url' => $this->trackingUrl->forTrack($code, $trackNumber),
            ];
        }

        return [
            //  OrderShipment.id is the shipment number, base64 (SalesGraphQl Resolver\Shipments).
            'id' => base64_encode($number),
            'number' => $number,
            'created_at' => self::utc($shipment['created_at'] ?? ''),
            'tracks' => $out,
        ];
    }

    /**
     * Order\Item::isChildrenCalculated() for a parent line: a bundle priced per child.
     *
     * @param array<string, mixed> $line
     */
    private static function childrenCalculated(array $line): bool
    {
        $options = $line['product_options'] ?? null;
        if (is_string($options) && $options !== '') {
            $options = json_decode($options, true);
        }

        return is_array($options)
            && isset($options['product_calculations'])
            && (int) $options['product_calculations'] === self::CALCULATE_CHILD;
    }

    /**
     * The website prints a comment as HTML with a few tags allowed; the app gets plain text.
     */
    public static function plainText(string $html): string
    {
        $text = preg_replace('#<br\s*/?>|</p>#i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * A UTC MySQL datetime (Magento's connections run in UTC) as ISO-8601 with "Z"; '' when empty.
     */
    public static function utc(mixed $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return '';
        }
        try {
            $date = new \DateTimeImmutable($value, new \DateTimeZone('UTC'));
        } catch (\Exception $e) {
            return '';
        }

        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * @return array{value: float, currency: string}
     */
    private static function money(mixed $value, string $currency): array
    {
        return ['value' => round((float) $value, 4), 'currency' => $currency];
    }

    /**
     * @return array{value: float, currency: string}|null
     */
    private static function moneyOrNull(float $value, string $currency): ?array
    {
        return abs($value) < self::EPSILON ? null : self::money($value, $currency);
    }
}
