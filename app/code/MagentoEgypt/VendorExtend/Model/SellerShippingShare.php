<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model;

use Vnecoms\VendorsApi\Api\Data\Sale\OrderInterface;

/**
 * The seller's share of the order's shipping, on the seller API's order totals.
 *
 * WHAT THE APP SHOWED ([CL036-TC90], 14zb93nvwph)
 * -----------------------------------------------
 * Order #3000000182: the backend says Shipping & Handling AED 10.00, Grand Total
 * AED 3,750.00; the vendor app said AED 0.00 and AED 3,740.00. Not a mapping slip
 * in the app — the API itself said 0. Vnecoms splits an order into one vendor
 * order per seller and only gives a vendor order shipping when the customer chose
 * a SELLER shipping method. Hub Market checks out with the store's Flat Rate, so
 * every vendor order on the site carried shipping_amount 0 (30 of 30 checked,
 * 2026-10-05), and its grand total was the order's minus the shipping.
 *
 * THE SHARE
 * ---------
 * Applied only when Vnecoms gave none of the order's vendor orders any shipping
 * (so seller shipping methods, should they ever be enabled, keep their own
 * figures) and the order has some. Each seller gets the order's shipping in
 * proportion to the shippable quantity of its items: top-level, non-virtual
 * order items. Flat Rate here is charged per item (carriers/flatrate/type = I,
 * AED 10), so for it the proportion is exact; a single-seller order simply gets
 * all of it. The same proportion is taken of the shipping tax, the shipping
 * discount, and what of the shipping has been invoiced and refunded, so
 * grand_total, total_paid, total_refunded and total_due stay consistent with
 * each other — total_due = grand_total - total_paid, as Magento keeps it.
 *
 * Display only: the vendor order row, commission and payouts are untouched.
 *
 * Also puts back the seller's own total_refunded, which the endpoints' join on
 * sales_order_grid replaced with the whole order's (hmRestoreOwnTotalRefunded).
 */
trait SellerShippingShare
{
    /** @var array<int, array{order: array<string, mixed>, qty: array<int, float>, total: float}|null> */
    private array $hmShippingShareCache = [];

    private function hmApplySellerShippingShare(OrderInterface $result): void
    {
        try {
            $this->hmRestoreOwnTotalRefunded($result);

            $vendorId = (int) $result->getVendorId();
            $share = $this->hmShippingShareFor((int) $result->getOrderId());
            if ($share === null || $vendorId <= 0 || empty($share['qty'][$vendorId])) {
                return;
            }

            $ratio = $share['qty'][$vendorId] / $share['total'];
            $order = $share['order'];

            /*
             * The method too: the vendor order row has none (Vnecoms only fills it for
             * seller methods), so the app's "Shipping & Handling Information" said
             * "No shipping information available" beside AED 10.00 of shipping.
             */
            $current = $result->__toArray();
            if (empty($current['shipping_description']) && !empty($order['shipping_description'])) {
                $result->setData('shipping_description', (string) $order['shipping_description']);
            }
            if (empty($current['shipping_method']) && !empty($order['shipping_method'])) {
                $result->setData('shipping_method', (string) $order['shipping_method']);
            }

            foreach (['', 'base_'] as $p) {
                $amount   = round((float) $order[$p . 'shipping_amount'] * $ratio, 4);
                $tax      = round((float) $order[$p . 'shipping_tax_amount'] * $ratio, 4);
                $incl     = round((float) ($order[$p . 'shipping_incl_tax'] ?: $order[$p . 'shipping_amount'] + $order[$p . 'shipping_tax_amount']) * $ratio, 4);
                $discount = round(abs((float) $order[$p . 'shipping_discount_amount']) * $ratio, 4);
                $net      = $incl - $discount;

                $shipping = (float) $order[$p . 'shipping_amount'];
                $paid     = $shipping > 0 ? $net * min(1.0, (float) $order[$p . 'shipping_invoiced'] / $shipping) : 0.0;
                $refunded = $shipping > 0 ? $net * min(1.0, (float) $order[$p . 'shipping_refunded'] / $shipping) : 0.0;

                $grandTotal = (float) $result->__toArray()[$p . 'grand_total'] + $net;
                $result->setData($p . 'shipping_amount', $amount);
                $result->setData($p . 'shipping_tax_amount', $tax);
                $result->setData($p . 'shipping_incl_tax', $incl);
                $result->setData($p . 'grand_total', round($grandTotal, 4));

                $data = $result->__toArray();
                $totalPaid = $data[$p . 'total_paid'] ?? null;
                if ($paid > 0 || $totalPaid !== null) {
                    $totalPaid = round((float) $totalPaid + $paid, 4);
                    $result->setData($p . 'total_paid', $totalPaid);
                }
                if ($refunded > 0) {
                    $result->setData($p . 'total_refunded', round((float) ($data[$p . 'total_refunded'] ?? 0) + $refunded, 4));
                }
                if (array_key_exists($p . 'total_due', $data) && $data[$p . 'total_due'] !== null) {
                    $result->setData($p . 'total_due', round(max(0.0, $grandTotal - (float) $totalPaid), 4));
                }
            }
        } catch (\Throwable $e) {
            // The order answers with Vnecoms' own figures rather than not at all.
            \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class)
                ->warning('Seller API shipping share skipped: ' . $e->getMessage());
        }
    }

    /**
     * The seller's own total_refunded, not the order's.
     *
     * Both seller order endpoints join sales_order_grid for the order's display
     * columns, and `total_refunded` is one of them, so it overwrote the vendor
     * order's own column: on #3000000134 all three sellers reported AED 2,750.00
     * refunded, the one refund of one of them. The vendor order row holds the
     * seller's figure (NULL when nothing of theirs was refunded).
     */
    private function hmRestoreOwnTotalRefunded(OrderInterface $result): void
    {
        $vendorOrderId = (int) $result->getEntityId();
        if ($vendorOrderId <= 0) {
            return;
        }

        $resource = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();
        $own = $connection->fetchOne(
            $connection->select()
                ->from($resource->getTableName('ves_vendor_sales_order'), ['total_refunded'])
                ->where('entity_id = ?', $vendorOrderId)
        );
        $result->setData('total_refunded', $own === false ? null : $own);
    }

    /**
     * @return array{order: array<string, mixed>, qty: array<int, float>, total: float}|null
     */
    private function hmShippingShareFor(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }
        if (array_key_exists($orderId, $this->hmShippingShareCache)) {
            return $this->hmShippingShareCache[$orderId];
        }

        $resource = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();

        $order = $connection->fetchRow(
            $connection->select()
                ->from($resource->getTableName('sales_order'), [
                    'shipping_amount', 'base_shipping_amount',
                    'shipping_tax_amount', 'base_shipping_tax_amount',
                    'shipping_incl_tax', 'base_shipping_incl_tax',
                    'shipping_discount_amount', 'base_shipping_discount_amount',
                    'shipping_invoiced', 'base_shipping_invoiced',
                    'shipping_refunded', 'base_shipping_refunded',
                    'shipping_description', 'shipping_method',
                ])
                ->where('entity_id = ?', $orderId)
        );
        $vendorShipping = (float) $connection->fetchOne(
            $connection->select()
                ->from($resource->getTableName('ves_vendor_sales_order'), ['SUM(ABS(COALESCE(shipping_amount, 0)))'])
                ->where('order_id = ?', $orderId)
        );

        $share = null;
        if ($order && (float) $order['shipping_amount'] > 0 && $vendorShipping == 0.0) {
            $qty = array_map('floatval', $connection->fetchPairs(
                $connection->select()
                    ->from($resource->getTableName('sales_order_item'), ['vendor_id', 'SUM(qty_ordered)'])
                    ->where('order_id = ?', $orderId)
                    ->where('parent_item_id IS NULL')
                    ->where('is_virtual = 0')
                    ->group('vendor_id')
            ));
            $total = array_sum($qty);
            if ($total > 0) {
                $share = ['order' => $order, 'qty' => $qty, 'total' => $total];
            }
        }

        return $this->hmShippingShareCache[$orderId] = $share;
    }
}
