<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;
use MagentoEgypt\OdooConnector\Model\Api\OdooClient;

/**
 * M->O order documents: creates a posted Odoo customer invoice (account.move,
 * out_invoice) for each Magento order invoice. Idempotent by ref = MAG-INV-<inc>.
 *
 * NOTE: Odoo delivery-order (stock.picking) VALIDATION for shipments is
 * environment-dependent (immediate-transfer / backorder wizards over RPC) and is
 * intentionally NOT auto-validated here; shipment tracking is synced on the order
 * (x_magento_tracking) and the confirmed sale.order already carries its delivery.
 */
class OrderDocuments
{
    private const INVOICE_MODEL = 'account.move';

    private OdooClient $odooClient;
    private bool $incomeResolved = false;
    private ?int $incomeAccountId = null;

    public function __construct(OdooClient $odooClient)
    {
        $this->odooClient = $odooClient;
    }

    /**
     * @return array<int, array{ref: string, status: string, id: int}>
     */
    public function syncInvoices(OrderInterface $order, int $odooSaleOrderId): array
    {
        $invoices = $order->getInvoiceCollection();
        if ($invoices === null || $invoices->getSize() === 0) {
            return [];
        }
        $partnerId = $this->partnerOfSaleOrder($odooSaleOrderId);
        if ($partnerId === null) {
            return [];
        }
        $income = $this->incomeAccount();

        $out = [];
        foreach ($invoices as $invoice) {
            $ref = 'MAG-INV-' . $invoice->getIncrementId();
            $existing = $this->odooClient->executeKw(
                self::INVOICE_MODEL,
                'search',
                [[['ref', '=', $ref], ['move_type', '=', 'out_invoice']]],
                ['limit' => 1]
            );
            if (is_array($existing) && isset($existing[0])) {
                $out[] = ['ref' => $ref, 'status' => 'exists', 'id' => (int)$existing[0]];
                continue;
            }

            $lines = [];
            foreach ($invoice->getItems() as $item) {
                $orderItem = $item->getOrderItem();
                if ($orderItem !== null && $orderItem->getParentItemId() !== null) {
                    continue;
                }
                $qty = (float)$item->getQty();
                if ($qty <= 0) {
                    continue;
                }
                $line = [
                    'quantity' => $qty,
                    'price_unit' => (float)$item->getPrice(),
                    'name' => (string)$item->getName(),
                    'tax_ids' => [[6, 0, []]],
                ];
                $productId = $this->resolveProduct((string)$item->getSku());
                if ($productId !== null) {
                    $line['product_id'] = $productId;
                }
                if ($income !== null) {
                    $line['account_id'] = $income;
                }
                $lines[] = [0, 0, $line];
            }
            if ($lines === []) {
                continue;
            }

            $moveId = (int)$this->odooClient->executeKw(self::INVOICE_MODEL, 'create', [[
                'move_type' => 'out_invoice',
                'partner_id' => $partnerId,
                'invoice_origin' => (string)$order->getIncrementId(),
                'ref' => $ref,
                'invoice_line_ids' => $lines,
            ]]);

            $status = 'draft';
            try {
                $this->odooClient->executeKw(self::INVOICE_MODEL, 'action_post', [[$moveId]]);
                $status = 'posted';
            } catch (\Throwable $e) {
                // leave as draft if Odoo accounting blocks the post (e.g. missing account)
            }
            $out[] = ['ref' => $ref, 'status' => $status, 'id' => $moveId];
        }

        return $out;
    }

    /**
     * M->O credit notes: account.move (out_refund) for each Magento credit memo.
     * Idempotent by ref = MAG-CM-<inc>. Mirrors syncInvoices().
     *
     * @return array<int, array{ref: string, status: string, id: int}>
     */
    public function syncCreditmemos(OrderInterface $order, int $odooSaleOrderId): array
    {
        $memos = $order->getCreditmemosCollection();
        if ($memos === null || $memos->getSize() === 0) {
            return [];
        }
        $partnerId = $this->partnerOfSaleOrder($odooSaleOrderId);
        if ($partnerId === null) {
            return [];
        }
        $income = $this->incomeAccount();

        $out = [];
        foreach ($memos as $memo) {
            $ref = 'MAG-CM-' . $memo->getIncrementId();
            $existing = $this->odooClient->executeKw(
                self::INVOICE_MODEL,
                'search',
                [[['ref', '=', $ref], ['move_type', '=', 'out_refund']]],
                ['limit' => 1]
            );
            if (is_array($existing) && isset($existing[0])) {
                $out[] = ['ref' => $ref, 'status' => 'exists', 'id' => (int)$existing[0]];
                continue;
            }

            $lines = [];
            foreach ($memo->getItems() as $item) {
                $orderItem = $item->getOrderItem();
                if ($orderItem !== null && $orderItem->getParentItemId() !== null) {
                    continue;
                }
                $qty = (float)$item->getQty();
                if ($qty <= 0) {
                    continue;
                }
                $line = [
                    'quantity' => $qty,
                    'price_unit' => (float)$item->getPrice(),
                    'name' => (string)$item->getName(),
                    'tax_ids' => [[6, 0, []]],
                ];
                $productId = $this->resolveProduct((string)$item->getSku());
                if ($productId !== null) {
                    $line['product_id'] = $productId;
                }
                if ($income !== null) {
                    $line['account_id'] = $income;
                }
                $lines[] = [0, 0, $line];
            }
            if ($lines === []) {
                continue;
            }

            $moveId = (int)$this->odooClient->executeKw(self::INVOICE_MODEL, 'create', [[
                'move_type' => 'out_refund',
                'partner_id' => $partnerId,
                'invoice_origin' => (string)$order->getIncrementId(),
                'ref' => $ref,
                'invoice_line_ids' => $lines,
            ]]);

            $status = 'draft';
            try {
                $this->odooClient->executeKw(self::INVOICE_MODEL, 'action_post', [[$moveId]]);
                $status = 'posted';
            } catch (\Throwable $e) {
                // leave as draft if Odoo accounting blocks the post
            }
            $out[] = ['ref' => $ref, 'status' => $status, 'id' => $moveId];
        }

        return $out;
    }

    /**
     * Best-effort: write Magento shipment tracking number(s) onto the sale order's Odoo
     * outgoing delivery picking(s) (carrier_tracking_ref). Does NOT validate pickings —
     * RPC transfer/backorder wizards are environment-dependent (see class note); tracking
     * also lives on the order as x_magento_tracking.
     */
    public function syncShipmentTracking(OrderInterface $order, int $odooSaleOrderId): void
    {
        try {
            $tracks = [];
            foreach ($order->getTracksCollection() as $track) {
                $num = trim((string)$track->getTrackNumber());
                if ($num !== '') {
                    $tracks[] = $num;
                }
            }
            if ($tracks === []) {
                return;
            }
            $pickings = $this->odooClient->executeKw(
                'stock.picking',
                'search',
                [[['sale_id', '=', $odooSaleOrderId], ['picking_type_code', '=', 'outgoing']]],
                ['limit' => 5]
            );
            if (is_array($pickings) && $pickings !== []) {
                $this->odooClient->executeKw('stock.picking', 'write', [
                    array_map('intval', $pickings),
                    ['carrier_tracking_ref' => implode(', ', array_unique($tracks))],
                ]);
            }
        } catch (\Throwable $e) {
            // non-fatal: tracking is also synced on the order (x_magento_tracking)
        }
    }

    /**
     * Best-effort: validate (mark "done") the sale order's outgoing delivery picking(s)
     * when the Magento order has shipments. NOTE: Odoo's button_validate may return an
     * immediate-transfer / backorder wizard action over RPC rather than completing — this
     * is environment-dependent, so the whole thing is wrapped best-effort and never breaks
     * the order sync (tracking is already on x_magento_tracking either way).
     */
    public function validateShippedPickings(OrderInterface $order, int $odooSaleOrderId): void
    {
        try {
            $shipments = $order->getShipmentsCollection();
            if ($shipments === null || $shipments->getSize() === 0) {
                return;
            }
            $pickings = $this->odooClient->executeKw(
                'stock.picking',
                'search',
                [[['sale_id', '=', $odooSaleOrderId], ['picking_type_code', '=', 'outgoing'], ['state', 'not in', ['done', 'cancel']]]],
                ['limit' => 10]
            );
            if (!is_array($pickings)) {
                return;
            }
            foreach ($pickings as $pickingId) {
                try {
                    $this->odooClient->executeKw('stock.picking', 'action_assign', [[(int)$pickingId]]);
                    $this->odooClient->executeKw(
                        'stock.picking',
                        'button_validate',
                        [[(int)$pickingId]],
                        ['context' => ['skip_backorder' => true, 'skip_sms' => true]]
                    );
                } catch (\Throwable $e) {
                    // per-picking best-effort: RPC validation may need a wizard we can't drive
                }
            }
        } catch (\Throwable $e) {
            // non-fatal
        }
    }

    private function partnerOfSaleOrder(int $saleOrderId): ?int
    {
        try {
            $rows = $this->odooClient->executeKw('sale.order', 'read', [[$saleOrderId]], ['fields' => ['partner_id']]);
            if (is_array($rows) && isset($rows[0]['partner_id'][0])) {
                return (int)$rows[0]['partner_id'][0];
            }
        } catch (\Throwable $e) {
            // unresolved
        }

        return null;
    }

    private function resolveProduct(string $sku): ?int
    {
        if ($sku === '') {
            return null;
        }
        try {
            $found = $this->odooClient->executeKw('product.product', 'search', [[['default_code', '=', $sku]]], ['limit' => 1]);
            if (is_array($found) && isset($found[0])) {
                return (int)$found[0];
            }
        } catch (\Throwable $e) {
            // unresolved
        }

        return null;
    }

    private function incomeAccount(): ?int
    {
        if ($this->incomeResolved) {
            return $this->incomeAccountId;
        }
        $this->incomeResolved = true;
        try {
            $found = $this->odooClient->executeKw('account.account', 'search', [[['account_type', '=', 'income']]], ['limit' => 1]);
            if (is_array($found) && isset($found[0])) {
                $this->incomeAccountId = (int)$found[0];
            }
        } catch (\Throwable $e) {
            // none
        }

        return $this->incomeAccountId;
    }
}
