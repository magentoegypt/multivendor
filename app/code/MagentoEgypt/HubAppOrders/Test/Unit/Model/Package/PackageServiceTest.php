<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppOrders\Test\Unit\Model\Package;

use Magento\Sales\Api\Data\OrderInterface;
use MagentoEgypt\HubApp\Api\SellerSummaryProviderInterface;
use MagentoEgypt\HubAppOrders\Model\Package\PackageBuilder;
use MagentoEgypt\HubAppOrders\Model\Package\PackageReader;
use MagentoEgypt\HubAppOrders\Model\Package\PackageService;
use MagentoEgypt\HubAppOrders\Model\Package\StatusLabels;
use MagentoEgypt\HubAppOrders\Model\Package\TrackingUrl;
use PHPUnit\Framework\TestCase;

/**
 * Packages for a page of orders: one read, one seller-summary call and one label call for all.
 */
final class PackageServiceTest extends TestCase
{
    private const STORE = 2;

    public function testAPageOfOrdersIsOneReadOneSellerCallAndOneLabelCall(): void
    {
        $reader = $this->createMock(PackageReader::class);
        $reader->expects(self::once())->method('read')->with([10, 11])->willReturn($this->rows());

        $sellers = $this->createMock(SellerSummaryProviderInterface::class);
        $sellers->expects(self::once())
            ->method('getByVendorIds')
            ->with([7, 0], self::STORE)
            ->willReturn([
                7 => ['code' => 'loly', 'name' => 'loly store', 'is_marketplace' => false],
                0 => ['code' => null, 'name' => 'Hub Market', 'is_marketplace' => true],
            ]);

        $labels = $this->createMock(StatusLabels::class);
        $labels->expects(self::once())
            ->method('labels')
            ->with(['complete', 'pending'], self::STORE)
            ->willReturn(['complete' => 'مكتمل', 'pending' => 'قيد الانتظار']);

        $service = new PackageService($reader, new PackageBuilder(new TrackingUrl([])), $sellers, $labels);
        $orders = [$this->order(10, 'processing'), $this->order(11, 'pending'), $this->order(10, 'processing')];

        $built = $service->forOrders($orders, self::STORE);

        self::assertSame([10, 11], array_keys($built));
        self::assertSame('loly store', $built[10][0]['seller']['name']);
        self::assertSame('مكتمل', $built[10][0]['status_label']);
        self::assertSame('complete', $built[10][0]['status_code']);
        self::assertArrayNotHasKey('vendor_id', $built[10][0]);
        self::assertSame('Hub Market', $built[11][0]['seller']['name']);
        self::assertSame('قيد الانتظار', $built[11][0]['status_label']);
        self::assertSame('AED', $built[11][0]['subtotal']['currency']);
    }

    public function testASellerWithoutSummaryLeavesTheSellerNull(): void
    {
        $reader = $this->createMock(PackageReader::class);
        $reader->method('read')->willReturn($this->rows());
        $sellers = $this->createMock(SellerSummaryProviderInterface::class);
        //  Vendor 7 is no longer approved: the provider leaves it out.
        $sellers->method('getByVendorIds')->willReturn([]);
        $labels = $this->createMock(StatusLabels::class);
        $labels->method('labels')->willReturn([]);

        $service = new PackageService($reader, new PackageBuilder(new TrackingUrl([])), $sellers, $labels);
        $built = $service->forOrders([$this->order(10, 'processing')], self::STORE);

        self::assertNull($built[10][0]['seller']);
        //  No label found: the code itself rather than nothing.
        self::assertSame('complete', $built[10][0]['status_label']);
    }

    public function testNoOrderNoRead(): void
    {
        $reader = $this->createMock(PackageReader::class);
        $reader->expects(self::never())->method('read');
        $sellers = $this->createMock(SellerSummaryProviderInterface::class);
        $sellers->expects(self::never())->method('getByVendorIds');
        $labels = $this->createMock(StatusLabels::class);
        $labels->expects(self::never())->method('labels');

        $service = new PackageService($reader, new PackageBuilder(new TrackingUrl([])), $sellers, $labels);

        self::assertSame([], $service->forOrders([$this->order(0, 'pending')], self::STORE));
    }

    private function order(int $id, string $status): OrderInterface
    {
        $order = $this->createMock(OrderInterface::class);
        $order->method('getEntityId')->willReturn($id ?: null);
        $order->method('getStatus')->willReturn($status);
        $order->method('getState')->willReturn($status === 'pending' ? 'new' : $status);
        $order->method('getOrderCurrencyCode')->willReturn('AED');
        $order->method('getBaseCurrencyCode')->willReturn('AED');

        return $order;
    }

    /**
     * Order 10: loly's vendor order 501 (complete). Order 11: one Hub Market line.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    private function rows(): array
    {
        $line = static fn (int $id, int $orderId, int $vendorId, int $vendorOrderId): array => [
            'item_id' => (string) $id,
            'order_id' => (string) $orderId,
            'parent_item_id' => null,
            'vendor_id' => (string) $vendorId,
            'vendor_order_id' => (string) $vendorOrderId,
            'product_options' => null,
            'row_total' => '25.0000',
            'tax_amount' => '0.0000',
            'discount_amount' => '0.0000',
            'discount_tax_compensation_amount' => '0.0000',
        ];

        return [
            'vendor_orders' => [[
                'entity_id' => '501', 'vendor_id' => '7', 'order_id' => '10', 'state' => 'complete',
                'status' => 'complete', 'subtotal' => '25.0000', 'discount_amount' => '0.0000',
                'tax_amount' => '0.0000', 'shipping_amount' => '0.0000', 'grand_total' => '25.0000',
                'shipping_method' => '', 'shipping_description' => null,
            ]],
            'items' => [$line(101, 10, 7, 501), $line(201, 11, 0, 0)],
            'shipments' => [],
            'shipment_items' => [],
            'tracks' => [],
            'comments' => [],
        ];
    }
}
