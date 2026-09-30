<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppOrders\Test\Unit\Model\Package;

use MagentoEgypt\HubAppOrders\Model\Package\PackageBuilder;
use MagentoEgypt\HubAppOrders\Model\Package\TrackingUrl;
use PHPUnit\Framework\TestCase;

/**
 * How an order splits into packages: Vnecoms' vendor orders, Hub Market's own lines, shipments and
 * the comments a store made visible, with totals as the vendor orders hold them.
 */
final class PackageBuilderTest extends TestCase
{
    private const ORDER = 10;

    public function testEachVendorOrderIsAPackageInTheOrderItsStoreFirstAppears(): void
    {
        $packages = $this->build()[self::ORDER];

        self::assertSame([7, 9, 0], array_column($packages, 'vendor_id'));
        self::assertSame([$this->uid(101)], $packages[0]['item_uids']);
        self::assertSame('complete', $packages[0]['status_code']);
        self::assertSame('complete', $packages[0]['state']);
        self::assertSame('processing', $packages[1]['status_code']);
    }

    public function testALineWithoutItsVendorOrderIdGoesToItsSellersVendorOrder(): void
    {
        //  102 carries vendor 9 but no vendor_order_id (Vnecoms back-fills it on read; we don't write).
        $packages = $this->build()[self::ORDER];

        self::assertSame([$this->uid(102), $this->uid(103)], $packages[1]['item_uids']);
    }

    public function testChildLinesAreNeverListed(): void
    {
        $uids = array_merge(...array_column($this->build()[self::ORDER], 'item_uids'));

        self::assertNotContains($this->uid(104), $uids);
        self::assertNotContains($this->uid(107), $uids);
        self::assertNotContains($this->uid(108), $uids);
        self::assertCount(5, $uids);
    }

    public function testTotalsAreWhatTheVendorOrderHolds(): void
    {
        $loly = $this->build()[self::ORDER][0];

        self::assertSame(['value' => 50.0, 'currency' => 'AED'], $loly['subtotal']);
        self::assertSame(['value' => 5.0, 'currency' => 'AED'], $loly['discount']);
        self::assertNull($loly['tax']);
        self::assertSame(['value' => 45.0, 'currency' => 'AED'], $loly['grand_total']);
    }

    public function testOneDeliveryChargeForTheWholeOrderIsNotAPerStoreDelivery(): void
    {
        //  No per-store method: the vendor order's 0 is not "free delivery".
        $loly = $this->build()[self::ORDER][0];

        self::assertNull($loly['shipping_amount']);
        self::assertNull($loly['shipping_method']);
    }

    public function testDeliveryChosenPerStoreIsTheStoresOwn(): void
    {
        $rows = $this->rows();
        $rows['vendor_orders'][0]['shipping_method'] = 'vtablerate_bestway||7';
        $rows['vendor_orders'][0]['shipping_description'] = 'Vendor Table Rate - Best Way';
        $rows['vendor_orders'][0]['shipping_amount'] = '10.0000';

        $loly = $this->build($rows)[self::ORDER][0];

        self::assertSame(['value' => 10.0, 'currency' => 'AED'], $loly['shipping_amount']);
        self::assertSame('Vendor Table Rate - Best Way', $loly['shipping_method']);
    }

    public function testHubMarketsOwnLinesTakeTheOrderStatusAndVnecomsTotals(): void
    {
        $own = $this->build()[self::ORDER][2];

        self::assertSame(0, $own['vendor_id']);
        self::assertSame([$this->uid(105), $this->uid(106)], $own['item_uids']);
        self::assertSame('processing', $own['status_code']);
        self::assertSame('processing', $own['state']);
        //  105: 20 + tax 1 - discount 2. 106 is a bundle priced per child: its children 10 + 15
        //  (tax 0.5) count, not its own 30.
        self::assertSame(['value' => 45.0, 'currency' => 'AED'], $own['subtotal']);
        self::assertSame(['value' => 1.5, 'currency' => 'AED'], $own['tax']);
        self::assertSame(['value' => 2.0, 'currency' => 'AED'], $own['discount']);
        self::assertSame(['value' => 44.5, 'currency' => 'AED'], $own['grand_total']);
        self::assertNull($own['shipping_amount']);
        self::assertSame([], $own['comments']);
    }

    public function testAShipmentBelongsToTheVendorOrderItWasMadeFrom(): void
    {
        $loly = $this->build()[self::ORDER][0];

        self::assertCount(1, $loly['shipments']);
        $shipment = $loly['shipments'][0];
        self::assertSame(base64_encode('000000031'), $shipment['id']);
        self::assertSame('000000031', $shipment['number']);
        self::assertSame('2026-09-29T06:10:00Z', $shipment['created_at']);
        self::assertSame(
            [
                [
                    'carrier_code' => 'dhl',
                    'carrier_title' => 'DHL',
                    'number' => '1234567890',
                    'tracking_url' => 'https://dhl.example/track?id=1234567890',
                ],
                [
                    'carrier_code' => 'custom',
                    'carrier_title' => 'Aramex',
                    'number' => '3345 1182',
                    'tracking_url' => null,
                ],
            ],
            $shipment['tracks']
        );
    }

    public function testAnAdminShipmentIsListedUnderEveryStoreWhoseLinesItHolds(): void
    {
        //  802 names no vendor order; it holds 104 (a child of mia's 103) and Hub Market's 105.
        $packages = $this->build()[self::ORDER];

        self::assertSame(['000000031'], array_column($packages[0]['shipments'], 'number'));
        self::assertSame(['000000032'], array_column($packages[1]['shipments'], 'number'));
        self::assertSame(['000000032'], array_column($packages[2]['shipments'], 'number'));
        self::assertSame([], $packages[1]['shipments'][0]['tracks']);
    }

    public function testOnlyTheStoresOwnVisibleCommentsAsPlainText(): void
    {
        $packages = $this->build()[self::ORDER];

        self::assertSame(
            [['message' => "Packed\nLeaving today & tomorrow", 'created_at' => '2026-09-28T07:05:00Z']],
            $packages[0]['comments']
        );
        //  Another seller's row on mia's vendor order, and an empty one, are left out.
        self::assertSame([], $packages[1]['comments']);
    }

    public function testAVendorOrderWithoutLinesIsNoPackage(): void
    {
        $rows = $this->rows();
        $rows['vendor_orders'][] = $this->vendorOrder(503, 12, 'processing', 0.0);

        self::assertSame([7, 9, 0], array_column($this->build($rows)[self::ORDER], 'vendor_id'));
    }

    public function testLinesOfASellerWithoutVendorOrderStayTheirs(): void
    {
        //  Vendor 12 was not a seller when the order was placed: no vendor order, not Hub Market.
        $rows = $this->rows();
        $rows['items'][] = $this->item(109, 12, 0, 18.0);

        $last = $this->build($rows)[self::ORDER][3];

        self::assertSame(12, $last['vendor_id']);
        self::assertSame([$this->uid(109)], $last['item_uids']);
        self::assertSame('processing', $last['status_code']);
    }

    public function testOrdersOfOneBatchStayApart(): void
    {
        $rows = $this->rows();
        $rows['items'][] = $this->item(201, 0, 0, 12.0, orderId: 11);
        $orders = [
            self::ORDER => ['status' => 'processing', 'state' => 'processing', 'currency' => 'AED'],
            11 => ['status' => 'pending', 'state' => 'new', 'currency' => 'AED'],
            12 => ['status' => 'pending', 'state' => 'new', 'currency' => 'AED'],
        ];

        $built = $this->builder()->build($orders, $rows);

        self::assertCount(3, $built[self::ORDER]);
        self::assertCount(1, $built[11]);
        self::assertSame([$this->uid(201)], $built[11][0]['item_uids']);
        self::assertSame('pending', $built[11][0]['status_code']);
        self::assertSame(['value' => 12.0, 'currency' => 'AED'], $built[11][0]['grand_total']);
        self::assertSame([], $built[11][0]['shipments']);
        self::assertSame([], $built[12]);
    }

    public function testAnUnnamedShipmentOfASingleStoreOrderIsThatStores(): void
    {
        $rows = [
            'vendor_orders' => [$this->vendorOrder(501, 7, 'processing', 50.0)],
            'items' => [$this->item(101, 7, 501, 50.0)],
            'shipments' => [$this->shipment(803, '000000040', 0)],
            'shipment_items' => [],
            'tracks' => [],
            'comments' => [],
        ];

        $packages = $this->build($rows)[self::ORDER];

        self::assertSame(['000000040'], array_column($packages[0]['shipments'], 'number'));
    }

    public function testPlainText(): void
    {
        self::assertSame("a\nb", PackageBuilder::plainText('a<br/>b'));
        self::assertSame('Tom & Jerry "ok"', PackageBuilder::plainText('<b>Tom &amp; Jerry</b> &quot;ok&quot;'));
        self::assertSame("one\n\ntwo", PackageBuilder::plainText("<p>one</p>\n\n\n\n<p>two</p>"));
        self::assertSame('', PackageBuilder::plainText('<p> </p>'));
    }

    public function testUtc(): void
    {
        self::assertSame('2026-09-28T07:05:00Z', PackageBuilder::utc('2026-09-28 07:05:00'));
        self::assertSame('', PackageBuilder::utc(null));
        self::assertSame('', PackageBuilder::utc('0000-00-00 00:00:00'));
        self::assertSame('', PackageBuilder::utc('not a date'));
    }

    /**
     * @param array<string, list<array<string, mixed>>>|null $rows
     * @return array<int, list<array<string, mixed>>>
     */
    private function build(?array $rows = null): array
    {
        return $this->builder()->build(
            [self::ORDER => ['status' => 'processing', 'state' => 'processing', 'currency' => 'AED']],
            $rows ?? $this->rows()
        );
    }

    private function builder(): PackageBuilder
    {
        return new PackageBuilder(new TrackingUrl(['dhl' => 'https://dhl.example/track?id=%s']));
    }

    /**
     * Order 10: loly (vendor 7, vendor order 501, complete), mia (vendor 9, vendor order 502) and
     * Hub Market's own lines; one shipment from loly's vendor order, one made from the order in admin.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function rows(): array
    {
        $bundle = $this->item(106, 0, 0, 30.0);
        $bundle['product_options'] = '{"product_calculations":0,"shipment_type":0}';
        $configurable = $this->item(103, 9, 502, 68.0);
        $configurable['product_options'] = '{"product_calculations":1}';

        return [
            'vendor_orders' => [
                $this->vendorOrder(501, 7, 'complete', 50.0, discount: 5.0),
                $this->vendorOrder(502, 9, 'processing', 493.0),
            ],
            'items' => [
                $this->item(101, 7, 501, 50.0, discount: 5.0),
                $this->item(102, 9, 0, 425.0),
                $configurable,
                $this->item(104, 9, 502, 0.0, parent: 103),
                $this->item(105, 0, 0, 20.0, tax: 1.0, discount: 2.0),
                $bundle,
                $this->item(107, 0, 0, 10.0, parent: 106, tax: 0.5),
                $this->item(108, 0, 0, 15.0, parent: 106),
            ],
            'shipments' => [
                $this->shipment(801, '000000031', 501),
                $this->shipment(802, '000000032', 0),
            ],
            'shipment_items' => [
                ['parent_id' => '802', 'order_item_id' => '104'],
                ['parent_id' => '802', 'order_item_id' => '105'],
            ],
            'tracks' => [
                ['entity_id' => '1', 'parent_id' => '801', 'order_id' => '10', 'track_number' => '1234567890',
                    'title' => 'DHL', 'carrier_code' => 'dhl'],
                ['entity_id' => '2', 'parent_id' => '801', 'order_id' => '10', 'track_number' => ' 3345 1182 ',
                    'title' => 'Aramex', 'carrier_code' => 'custom'],
                ['entity_id' => '3', 'parent_id' => '801', 'order_id' => '10', 'track_number' => '',
                    'title' => 'Empty', 'carrier_code' => 'custom'],
            ],
            'comments' => [
                ['entity_id' => '1', 'parent_id' => '10', 'vendor_id' => '7', 'vendor_order_status' => '501',
                    'comment' => 'Packed<br>Leaving today &amp; tomorrow', 'created_at' => '2026-09-28 07:05:00'],
                ['entity_id' => '2', 'parent_id' => '10', 'vendor_id' => '7', 'vendor_order_status' => '502',
                    'comment' => 'Not mia\'s', 'created_at' => '2026-09-28 08:00:00'],
                ['entity_id' => '3', 'parent_id' => '10', 'vendor_id' => '9', 'vendor_order_status' => '502',
                    'comment' => '<p> </p>', 'created_at' => '2026-09-28 09:00:00'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function vendorOrder(int $id, int $vendorId, string $status, float $subtotal, float $discount = 0.0): array
    {
        return [
            'entity_id' => (string) $id,
            'vendor_id' => (string) $vendorId,
            'order_id' => (string) self::ORDER,
            'state' => $status,
            'status' => $status,
            'subtotal' => sprintf('%.4f', $subtotal),
            'discount_amount' => sprintf('%.4f', $discount),
            'tax_amount' => '0.0000',
            'shipping_amount' => '0.0000',
            'grand_total' => sprintf('%.4f', $subtotal - $discount),
            'shipping_method' => '',
            'shipping_description' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function item(
        int $id,
        int $vendorId,
        int $vendorOrderId,
        float $rowTotal,
        ?int $parent = null,
        float $tax = 0.0,
        float $discount = 0.0,
        int $orderId = self::ORDER
    ): array {
        return [
            'item_id' => (string) $id,
            'order_id' => (string) $orderId,
            'parent_item_id' => $parent === null ? null : (string) $parent,
            'vendor_id' => (string) $vendorId,
            'vendor_order_id' => (string) $vendorOrderId,
            'product_options' => null,
            'row_total' => sprintf('%.4f', $rowTotal),
            'tax_amount' => sprintf('%.4f', $tax),
            'discount_amount' => sprintf('%.4f', $discount),
            'discount_tax_compensation_amount' => '0.0000',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function shipment(int $id, string $number, int $vendorOrderId): array
    {
        return [
            'entity_id' => (string) $id,
            'order_id' => (string) self::ORDER,
            'increment_id' => $number,
            'created_at' => '2026-09-29 06:10:00',
            'vendor_order_id' => (string) $vendorOrderId,
        ];
    }

    private function uid(int $itemId): string
    {
        return base64_encode((string) $itemId);
    }
}
