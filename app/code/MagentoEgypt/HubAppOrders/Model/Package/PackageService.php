<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppOrders\Model\Package;

use Magento\Sales\Api\Data\OrderInterface;
use MagentoEgypt\HubApp\Api\SellerSummaryProviderInterface;

/**
 * HmOrderPackage values for many orders at once: one read of each table (PackageReader), one
 * seller-summary call and one status-label call for all of them, whatever the number of orders.
 *
 * Only orders the caller already holds are read, and only by their ids: the CustomerOrder above
 * the field was authorised by the core query that produced it (customer.orders, guestOrder,
 * guestOrderByToken, placeOrder, cancelOrder), so nothing here takes an order id from the request.
 */
class PackageService
{
    public function __construct(
        private readonly PackageReader $reader,
        private readonly PackageBuilder $builder,
        private readonly SellerSummaryProviderInterface $sellerSummaries,
        private readonly StatusLabels $statusLabels
    ) {
    }

    /**
     * @param OrderInterface[] $orders
     * @return array<int, list<array<string, mixed>>> order entity id => HmOrderPackage values
     */
    public function forOrders(array $orders, int $storeId): array
    {
        $info = [];
        foreach ($orders as $order) {
            $orderId = (int) $order->getEntityId();
            if ($orderId <= 0 || isset($info[$orderId])) {
                continue;
            }
            $info[$orderId] = [
                'status' => (string) $order->getStatus(),
                'state' => (string) $order->getState(),
                'currency' => (string) ($order->getOrderCurrencyCode() ?: $order->getBaseCurrencyCode()),
            ];
        }
        if (!$info) {
            return [];
        }

        $built = $this->builder->build($info, $this->reader->read(array_keys($info)));

        $vendorIds = [];
        $codes = [];
        foreach ($built as $packages) {
            foreach ($packages as $package) {
                $vendorIds[(int) $package['vendor_id']] = (int) $package['vendor_id'];
                $codes[(string) $package['status_code']] = (string) $package['status_code'];
            }
        }
        $sellers = $vendorIds ? $this->sellerSummaries->getByVendorIds(array_values($vendorIds), $storeId) : [];
        $labels = $codes ? $this->statusLabels->labels(array_values($codes), $storeId) : [];

        foreach ($built as $orderId => $packages) {
            foreach ($packages as $index => $package) {
                $code = (string) $package['status_code'];
                $built[$orderId][$index]['seller'] = $sellers[(int) $package['vendor_id']] ?? null;
                $built[$orderId][$index]['status_label'] = $labels[$code] ?? $code;
                unset($built[$orderId][$index]['vendor_id']);
            }
        }

        return $built;
    }
}
