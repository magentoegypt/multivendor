<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Inventory;

use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\OdooConnector\Model\Api\OdooClient;
use MagentoEgypt\OdooConnector\Model\EntityMap;
use MagentoEgypt\OdooConnector\Model\Mapping\MapManager;
use MagentoEgypt\OdooConnector\Model\Sync\Checksum;

/**
 * Pushes MSI on-hand (source_code + sku + qty) to Odoo on-hand via the Odoo 19
 * inventory-adjustment flow (stock.quant.inventory_quantity + action_apply_inventory).
 * Only acts on SKUs that resolve to an Odoo product; idempotent (re-applying the
 * same quantity is a no-op adjustment).
 */
class InventoryPusher
{
    private const ENTITY_TYPE = 'inventory_source_item';
    private const ODOO_MODEL = 'product.product';

    private OdooClient $odooClient;
    private MapManager $mapManager;
    private Checksum $checksum;
    private ResourceConnection $resourceConnection;

    private ?int $locationId = null;

    public function __construct(
        OdooClient $odooClient,
        MapManager $mapManager,
        Checksum $checksum,
        ResourceConnection $resourceConnection
    ) {
        $this->odooClient = $odooClient;
        $this->mapManager = $mapManager;
        $this->checksum = $checksum;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @return array{action: string, qty: float, odoo_product_id?: int, reason?: string}
     */
    public function push(string $sku, string $sourceCode, float $qty, string $correlationId): array
    {
        $productIds = $this->odooClient->executeKw(self::ODOO_MODEL, 'search', [[['default_code', '=', $sku]]], ['limit' => 1]);
        if (!is_array($productIds) || !isset($productIds[0])) {
            return ['action' => 'skipped', 'qty' => $qty, 'reason' => 'no Odoo product for sku'];
        }
        $productId = (int)$productIds[0];

        $locationId = $this->resolveLocationId();
        if ($locationId === 0) {
            return ['action' => 'skipped', 'qty' => $qty, 'reason' => 'no stock location'];
        }

        $quants = $this->odooClient->executeKw(
            'stock.quant',
            'search',
            [[['product_id', '=', $productId], ['location_id', '=', $locationId]]],
            ['limit' => 1]
        );

        if (is_array($quants) && isset($quants[0])) {
            $quantId = (int)$quants[0];
            $this->odooClient->executeKw('stock.quant', 'write', [[$quantId], ['inventory_quantity' => $qty]]);
        } else {
            $quantId = (int)$this->odooClient->executeKw(
                'stock.quant',
                'create',
                [['product_id' => $productId, 'location_id' => $locationId, 'inventory_quantity' => $qty]]
            );
        }
        $this->odooClient->executeKw('stock.quant', 'action_apply_inventory', [[$quantId]]);

        $checksum = $this->checksum->hash(['source' => $sourceCode, 'sku' => $sku, 'qty' => $qty]);
        $this->mapManager->link([
            'entity_type' => self::ENTITY_TYPE,
            'magento_natural_key' => $sourceCode . ':' . $sku,
            'magento_id' => $sku,
            'odoo_model' => self::ODOO_MODEL,
            'odoo_id' => $productId,
            'magento_checksum' => $checksum,
            'odoo_checksum' => $checksum,
            'last_direction' => EntityMap::DIRECTION_M2O,
            'sync_status' => EntityMap::STATUS_LINKED,
            'website_id' => 0,
            'last_correlation_id' => $correlationId,
        ]);

        return ['action' => 'set', 'qty' => $qty, 'odoo_product_id' => $productId];
    }

    /**
     * Re-read the current MSI qty for (source, sku) and push it. Used by the consumer.
     *
     * @return array{action: string, qty: float, odoo_product_id?: int, reason?: string}
     */
    public function pushBySourceSku(string $sourceCode, string $sku, string $correlationId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('inventory_source_item');
        $qty = $connection->fetchOne(
            "SELECT quantity FROM {$table} WHERE source_code = ? AND sku = ?",
            [$sourceCode, $sku]
        );
        if ($qty === false) {
            return ['action' => 'skipped', 'qty' => 0.0, 'reason' => 'no source item'];
        }

        return $this->push($sku, $sourceCode, (float)$qty, $correlationId);
    }

    private function resolveLocationId(): int
    {
        if ($this->locationId === null) {
            $warehouses = $this->odooClient->executeKw('stock.warehouse', 'search_read', [[]], ['fields' => ['lot_stock_id'], 'limit' => 1]);
            $this->locationId = (is_array($warehouses) && isset($warehouses[0]['lot_stock_id'][0]))
                ? (int)$warehouses[0]['lot_stock_id'][0]
                : 0;
        }

        return $this->locationId;
    }
}
