<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use MagentoEgypt\OdooConnector\Helper\Config;
use MagentoEgypt\OdooConnector\Model\Api\OdooClient;
use MagentoEgypt\OdooConnector\Model\EntityMap;
use MagentoEgypt\OdooConnector\Model\Mapping\MapManager;
use MagentoEgypt\OdooConnector\Model\Product\ProductPusher;
use MagentoEgypt\OdooConnector\Model\Sync\Checksum;

/**
 * Pushes a Magento order to an Odoo sale.order (master order: one Magento order
 * -> one Odoo sale.order). Orders are Magento-authoritative and create-once:
 * once mapped, re-running never recreates (sale.orders have state). Resolves the
 * partner (find/create res.partner by email) and each line's product (via the
 * product map / product.product by SKU, cascade-creating from Magento if absent).
 *
 * SAFETY: only READS the Magento order; never writes increment_id or any
 * sequence table. The Magento increment_id is stored on Odoo as client_order_ref.
 *
 * NOTE: per-vendor split (one order -> N sale.orders) applies only when items
 * carry a vendor_id and requires vendor->res.partner mapping; this install's
 * orders have vendor_id=null, so the master order is correct here.
 */
class OrderPusher
{
    private const ENTITY_TYPE = 'order';
    private const ODOO_MODEL = 'sale.order';

    private OrderRepositoryInterface $orderRepository;
    private OdooClient $odooClient;
    private MapManager $mapManager;
    private ProductPusher $productPusher;
    private Checksum $checksum;
    private Config $config;
    private OrderDocuments $orderDocuments;

    /** @var array<string, int|null> */
    private array $countryCache = [];
    /** @var array<string, int|null> */
    private array $stateCache = [];
    /** @var array<string, int|null> */
    private array $currencyCache = [];
    private ?bool $saleLineHasTaxId = null;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        OdooClient $odooClient,
        MapManager $mapManager,
        ProductPusher $productPusher,
        Checksum $checksum,
        Config $config,
        OrderDocuments $orderDocuments
    ) {
        $this->orderRepository = $orderRepository;
        $this->odooClient = $odooClient;
        $this->mapManager = $mapManager;
        $this->productPusher = $productPusher;
        $this->checksum = $checksum;
        $this->config = $config;
        $this->orderDocuments = $orderDocuments;
    }

    /**
     * @return array{action: string, increment_id: string, odoo_id?: int, lines?: int, reason?: string}
     */
    public function pushById(int $orderId, string $correlationId): array
    {
        $order = $this->orderRepository->get($orderId);
        $incrementId = (string)$order->getIncrementId();
        $companyId = $this->config->getOdooCompanyId($order->getStoreId());
        $orderFields = $this->buildOrderFields($order);

        // Create-once: never recreate a mapped order — but DO refresh the mutable
        // fields (status, tracking, …) since they change over the order's life.
        $map = $this->mapManager->findByNaturalKey(self::ENTITY_TYPE, $incrementId, 0);
        if ($map !== null && $map->getData('odoo_id')) {
            $odooId = (int)$map->getData('odoo_id');
            try {
                $this->applyOrderFields($odooId, $order, $orderFields);

                return ['action' => 'exists', 'increment_id' => $incrementId, 'odoo_id' => $odooId];
            } catch (\MagentoEgypt\OdooConnector\Model\Api\OdooException $e) {
                if (!$e->isMissingRecord()) {
                    throw $e;
                }
                // Stale link (order gone after the Odoo migration) — fall through to
                // re-attach by client_order_ref / recreate below.
            }
        }

        // Re-attach to an existing Odoo sale.order by client_order_ref when the map has no link
        // (idempotent vs Odoo even after the entity map is cleared — never recreates the order).
        $existing = $this->odooClient->executeKw(self::ODOO_MODEL, 'search', [[['client_order_ref', '=', $incrementId], ['state', '!=', 'cancel']]], ['limit' => 1]);
        if (is_array($existing) && isset($existing[0])) {
            $odooId = (int)$existing[0];
            $this->mapManager->link([
                'entity_type' => self::ENTITY_TYPE,
                'magento_natural_key' => $incrementId,
                'magento_id' => (string)$order->getEntityId(),
                'odoo_model' => self::ODOO_MODEL,
                'odoo_id' => $odooId,
                'last_direction' => EntityMap::DIRECTION_M2O,
                'sync_status' => EntityMap::STATUS_LINKED,
                'website_id' => 0,
                'odoo_company_id' => $companyId,
                'last_correlation_id' => $correlationId,
            ]);
            $this->applyOrderFields($odooId, $order, $orderFields);

            return ['action' => 'attached', 'increment_id' => $incrementId, 'odoo_id' => $odooId];
        }

        $lines = [];
        foreach ($order->getItems() as $item) {
            if ($item->getParentItemId() !== null) {
                continue; // skip child items of composite products
            }
            $productId = $this->resolveOdooProduct($item);
            if ($productId === null) {
                continue;
            }
            $lineVals = [
                'product_id' => $productId,
                'product_uom_qty' => (float)$item->getQtyOrdered(),
                'price_unit' => (float)$item->getPrice(),
                'name' => (string)$item->getName(),
            ];
            // Clear Odoo's default tax so it doesn't recompute/inflate the line (Magento
            // is the tax authority) — but only when sale.order.line actually has tax_id.
            // This Odoo 19 has no `account` module, so the field is absent and sending it
            // aborts the create. (Full per-rate tax mapping is a config follow-up.)
            if ($this->saleLineHasTaxId()) {
                $lineVals['tax_id'] = [[6, 0, []]];
            }
            // Discounts -> Odoo per-line discount %.
            $discountPercent = (float)$item->getDiscountPercent();
            if ($discountPercent > 0) {
                $lineVals['discount'] = $discountPercent;
            }
            $lines[] = [0, 0, $lineVals];
        }

        if (empty($lines)) {
            return ['action' => 'skipped', 'increment_id' => $incrementId, 'reason' => 'no resolvable product lines'];
        }

        $partnerId = $this->resolvePartner($order);
        $createVals = [
            'partner_id' => $partnerId,
            'client_order_ref' => $incrementId,
            'order_line' => $lines,
        ] + $orderFields;
        if ($companyId !== null) {
            $createVals['company_id'] = $companyId;
        }
        // Preserve the original Magento order date.
        $createdAt = trim((string)$order->getCreatedAt());
        if ($createdAt !== '') {
            $createVals['date_order'] = $createdAt;
        }
        // Order currency -> Odoo res.currency. NOTE: sale.order.currency_id is derived
        // from the pricelist in standard Odoo, so this can be overridden (verified: an
        // EGP order landed as the pricelist's USD). A pricelist_id map is the real fix.
        $currencyId = $this->resolveCurrencyId((string)$order->getOrderCurrencyCode());
        if ($currencyId !== null) {
            $createVals['currency_id'] = $currencyId;
        }
        // Customer note -> Odoo order note.
        $note = trim((string)$order->getCustomerNote());
        if ($note !== '') {
            $createVals['note'] = $note;
        }
        // Billing/shipping addresses -> Odoo invoice/delivery child contacts.
        $invoicePartnerId = $this->resolveAddressPartner($partnerId, $order->getBillingAddress(), 'invoice');
        if ($invoicePartnerId !== null) {
            $createVals['partner_invoice_id'] = $invoicePartnerId;
        }
        $shippingPartnerId = $this->resolveAddressPartner($partnerId, $this->shippingAddress($order), 'delivery');
        if ($shippingPartnerId !== null) {
            $createVals['partner_shipping_id'] = $shippingPartnerId;
        }
        $odooId = (int)$this->odooClient->executeKw(self::ODOO_MODEL, 'create', [$createVals]);

        $checksum = $this->checksum->hash([
            'increment_id' => $incrementId,
            'grand_total' => (float)$order->getGrandTotal(),
            'lines' => count($lines),
        ]);
        $this->mapManager->link([
            'entity_type' => self::ENTITY_TYPE,
            'magento_natural_key' => $incrementId,
            'magento_id' => (string)$order->getEntityId(),
            'odoo_model' => self::ODOO_MODEL,
            'odoo_id' => $odooId,
            'magento_checksum' => $checksum,
            'odoo_checksum' => $checksum,
            'last_direction' => EntityMap::DIRECTION_M2O,
            'sync_status' => EntityMap::STATUS_LINKED,
            'website_id' => 0,
            'odoo_company_id' => $companyId,
            'last_correlation_id' => $correlationId,
        ]);
        $this->applyOrderFields($odooId, $order, []);

        return ['action' => 'create', 'increment_id' => $incrementId, 'odoo_id' => $odooId, 'lines' => count($lines)];
    }

    /**
     * Mutable order fields synced on every push (status, methods, tracking, discount).
     *
     * @return array<string, mixed>
     */
    private function buildOrderFields(OrderInterface $order): array
    {
        $fields = ['x_magento_status' => (string)$order->getStatus()];

        $shipping = (string)$order->getShippingDescription();
        if ($shipping === '') {
            $shipping = (string)$order->getShippingMethod();
        }
        if ($shipping !== '') {
            $fields['x_magento_shipping_method'] = $shipping;
        }
        try {
            $payment = $order->getPayment();
            if ($payment !== null && $payment->getMethod()) {
                $fields['x_magento_payment_method'] = (string)$payment->getMethod();
            }
        } catch (\Throwable $e) {
            // no payment
        }
        $discount = abs((float)$order->getDiscountAmount());
        if ($discount > 0.0) {
            $fields['x_magento_discount_amount'] = $discount;
        }
        $shipping = (float)$order->getShippingAmount();
        if ($shipping > 0.0) {
            $fields['x_magento_shipping_amount'] = $shipping;
        }
        $track = $this->firstTrackNumber($order);
        if ($track !== null) {
            $fields['x_magento_tracking'] = $track;
        }

        return $fields;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function applyOrderFields(int $odooId, OrderInterface $order, array $fields): void
    {
        try {
            if ($fields !== []) {
                $this->odooClient->executeKw(self::ODOO_MODEL, 'write', [[$odooId], $fields]);
            }
        } catch (\MagentoEgypt\OdooConnector\Model\Api\OdooException $e) {
            // A stale map link (the Odoo record was removed — e.g. orders lost in the
            // 16->19 migration) must bubble up so pushById can re-attach by
            // client_order_ref or recreate the order (otherwise stale-mapped orders
            // silently never self-heal). Other Odoo errors keep the refresh best-effort.
            if ($e->isMissingRecord()) {
                throw $e;
            }
        } catch (\Throwable $e) {
            // non-fatal: field refresh is best-effort
        }
        $this->stateTransition($odooId, $order);
        $this->syncDocuments($odooId, $order);
    }

    /**
     * Create the Odoo customer invoice(s) for a Magento order that has been invoiced.
     */
    private function syncDocuments(int $odooId, OrderInterface $order): void
    {
        try {
            $invoices = $order->getInvoiceCollection();
            if ($invoices !== null && $invoices->getSize() > 0) {
                $this->orderDocuments->syncInvoices($order, $odooId);
            }
        } catch (\Throwable $e) {
            // non-fatal: document sync is best-effort
        }
    }

    /**
     * Reflect the Magento order lifecycle in the Odoo sale.order state:
     * placed (processing/complete/closed) -> confirm; canceled -> cancel.
     */
    private function stateTransition(int $odooId, OrderInterface $order): void
    {
        $state = strtolower((string)$order->getState());
        try {
            $read = $this->odooClient->executeKw(self::ODOO_MODEL, 'read', [[$odooId]], ['fields' => ['state']]);
            $current = (is_array($read) && isset($read[0]['state'])) ? (string)$read[0]['state'] : '';
            if ($state === 'canceled' && $current !== 'cancel') {
                $this->odooClient->executeKw(self::ODOO_MODEL, 'action_cancel', [[$odooId]]);
            } elseif (in_array($state, ['processing', 'complete', 'closed'], true) && $current === 'draft') {
                $this->odooClient->executeKw(self::ODOO_MODEL, 'action_confirm', [[$odooId]]);
            }
        } catch (\Throwable $e) {
            // non-fatal: state transition is best-effort (Odoo may block on its own rules)
        }
    }

    private function firstTrackNumber(OrderInterface $order): ?string
    {
        try {
            foreach ($order->getTracksCollection() as $track) {
                $num = trim((string)$track->getTrackNumber());
                if ($num !== '') {
                    return $num;
                }
            }
        } catch (\Throwable $e) {
            // no shipments / tracks
        }

        return null;
    }

    private function resolvePartner(OrderInterface $order): int
    {
        $email = trim((string)$order->getCustomerEmail());
        if ($email !== '') {
            $found = $this->odooClient->executeKw('res.partner', 'search', [[['email', '=', $email]]], ['limit' => 1]);
            if (is_array($found) && isset($found[0])) {
                return (int)$found[0];
            }
        }

        $name = trim(($order->getCustomerFirstname() ?? '') . ' ' . ($order->getCustomerLastname() ?? ''));
        if ($name === '' && $order->getBillingAddress()) {
            $billing = $order->getBillingAddress();
            $name = trim(($billing->getFirstname() ?? '') . ' ' . ($billing->getLastname() ?? ''));
        }
        if ($name === '') {
            $name = $email !== '' ? $email : 'Magento Customer';
        }

        return (int)$this->odooClient->executeKw('res.partner', 'create', [['name' => $name, 'email' => $email !== '' ? $email : false]]);
    }

    /**
     * Find/create the Odoo invoice (billing) or delivery (shipping) child contact for
     * an order address under the order's partner. One child per type is reused.
     *
     * @param \Magento\Sales\Api\Data\OrderAddressInterface|null $address
     */
    private function resolveAddressPartner(int $parentId, $address, string $type): ?int
    {
        if ($address === null) {
            return null;
        }
        try {
            $vals = $this->addressVals($address);
            $vals['type'] = $type;
            $vals['parent_id'] = $parentId;
            $existing = $this->odooClient->executeKw(
                'res.partner',
                'search',
                [[['parent_id', '=', $parentId], ['type', '=', $type]]],
                ['limit' => 1]
            );
            if (is_array($existing) && isset($existing[0])) {
                $id = (int)$existing[0];
                $this->odooClient->executeKw('res.partner', 'write', [[$id], $vals]);

                return $id;
            }

            return (int)$this->odooClient->executeKw('res.partner', 'create', [$vals]);
        } catch (\Throwable $e) {
            return null; // non-fatal: the order still syncs against the main partner
        }
    }

    /**
     * res.partner values from a Magento order address (country/region resolved to Odoo ids).
     *
     * @param \Magento\Sales\Api\Data\OrderAddressInterface $address
     * @return array<string, mixed>
     */
    private function addressVals($address): array
    {
        $name = trim(($address->getFirstname() ?? '') . ' ' . ($address->getLastname() ?? ''));
        $vals = ['name' => $name !== '' ? $name : 'Address'];
        $street = $address->getStreet();
        if (is_array($street)) {
            if (isset($street[0]) && $street[0] !== '') {
                $vals['street'] = (string)$street[0];
            }
            if (isset($street[1]) && $street[1] !== '') {
                $vals['street2'] = (string)$street[1];
            }
        }
        if ($address->getCity()) {
            $vals['city'] = (string)$address->getCity();
        }
        if ($address->getPostcode()) {
            $vals['zip'] = (string)$address->getPostcode();
        }
        if ($address->getTelephone()) {
            $vals['phone'] = (string)$address->getTelephone();
        }
        $countryCode = trim((string)$address->getCountryId());
        if ($countryCode !== '') {
            $countryId = $this->resolveCountryId($countryCode);
            if ($countryId !== null) {
                $vals['country_id'] = $countryId;
                $regionCode = trim((string)$address->getRegionCode());
                if ($regionCode !== '') {
                    $stateId = $this->resolveStateId($regionCode, $countryId);
                    if ($stateId !== null) {
                        $vals['state_id'] = $stateId;
                    }
                }
            }
        }

        return $vals;
    }

    private function shippingAddress(OrderInterface $order)
    {
        return method_exists($order, 'getShippingAddress') ? $order->getShippingAddress() : null;
    }

    private function resolveCountryId(string $isoCode): ?int
    {
        $isoCode = strtoupper(trim($isoCode));
        if ($isoCode === '') {
            return null;
        }
        if (array_key_exists($isoCode, $this->countryCache)) {
            return $this->countryCache[$isoCode];
        }
        try {
            $found = $this->odooClient->executeKw('res.country', 'search', [[['code', '=', $isoCode]]], ['limit' => 1]);
            $id = (is_array($found) && isset($found[0])) ? (int)$found[0] : null;
        } catch (\Throwable $e) {
            $id = null;
        }

        return $this->countryCache[$isoCode] = $id;
    }

    private function resolveStateId(string $regionCode, int $countryId): ?int
    {
        $regionCode = strtoupper(trim($regionCode));
        $key = $countryId . ':' . $regionCode;
        if (array_key_exists($key, $this->stateCache)) {
            return $this->stateCache[$key];
        }
        try {
            $found = $this->odooClient->executeKw(
                'res.country.state',
                'search',
                [[['code', '=', $regionCode], ['country_id', '=', $countryId]]],
                ['limit' => 1]
            );
            $id = (is_array($found) && isset($found[0])) ? (int)$found[0] : null;
        } catch (\Throwable $e) {
            $id = null;
        }

        return $this->stateCache[$key] = $id;
    }

    private function resolveCurrencyId(string $code): ?int
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }
        if (array_key_exists($code, $this->currencyCache)) {
            return $this->currencyCache[$code];
        }
        try {
            // active_test=false so archived currencies still resolve.
            $found = $this->odooClient->executeKw(
                'res.currency',
                'search',
                [[['name', '=', $code]]],
                ['limit' => 1, 'context' => ['active_test' => false]]
            );
            $id = (is_array($found) && isset($found[0])) ? (int)$found[0] : null;
        } catch (\Throwable $e) {
            $id = null;
        }

        return $this->currencyCache[$code] = $id;
    }

    /**
     * Whether sale.order.line has a tax_id field in this Odoo (the `account` module may
     * not be installed). Cached per run; false on error so a missing field never aborts
     * the order create.
     */
    private function saleLineHasTaxId(): bool
    {
        if ($this->saleLineHasTaxId === null) {
            try {
                $fg = $this->odooClient->executeKw('sale.order.line', 'fields_get', [['tax_id']], ['attributes' => []]);
                $this->saleLineHasTaxId = is_array($fg) && array_key_exists('tax_id', $fg);
            } catch (\Throwable $e) {
                $this->saleLineHasTaxId = false;
            }
        }

        return $this->saleLineHasTaxId;
    }

    private function resolveOdooProduct(OrderItemInterface $item): ?int
    {
        $sku = (string)$item->getSku();

        $found = $this->odooClient->executeKw('product.product', 'search', [[['default_code', '=', $sku]]], ['limit' => 1]);
        if (is_array($found) && isset($found[0])) {
            return (int)$found[0];
        }

        // Cascade-create the product from Magento, then re-resolve.
        try {
            $this->productPusher->pushById((int)$item->getProductId(), 'order-cascade');
            $found = $this->odooClient->executeKw('product.product', 'search', [[['default_code', '=', $sku]]], ['limit' => 1]);
            if (is_array($found) && isset($found[0])) {
                return (int)$found[0];
            }
        } catch (\Throwable $e) {
            // unresolvable — skip the line
        }

        return null;
    }
}
