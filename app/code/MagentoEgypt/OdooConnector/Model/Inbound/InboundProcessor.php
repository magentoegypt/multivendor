<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Inbound;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use MagentoEgypt\OdooConnector\Logger\Logger;
use MagentoEgypt\OdooConnector\Model\EntityMap;
use MagentoEgypt\OdooConnector\Model\Log\AuditLogger;
use MagentoEgypt\OdooConnector\Model\Mapping\MapManager;
use MagentoEgypt\OdooConnector\Model\Sync\SyncContext;

/**
 * Applies an inbound (Odoo -> Magento) sync envelope:
 *  - resolves the existing mapping (Magento is master here, so inbound is
 *    update-only — it never creates a Magento record);
 *  - echo-suppresses (incoming checksum == our last-synced Odoo checksum);
 *  - applies the change, re-links the map as o2m, and audit-logs.
 *
 * Envelope shape: {entity_type, operation, natural_key, payload{}, checksum, correlation_id}.
 */
class InboundProcessor
{
    private const ENTITY_PRODUCT = 'product';
    private const ENTITY_CUSTOMER = 'customer_buyer';
    private const ENTITY_ORDER = 'order';
    private const ENTITY_INVENTORY = 'inventory_source_item';

    private MapManager $mapManager;
    private ProductRepositoryInterface $productRepository;
    private CustomerRepositoryInterface $customerRepository;
    private OrderRepositoryInterface $orderRepository;
    private AuditLogger $audit;
    private Logger $logger;
    private SyncContext $syncContext;
    private ResourceConnection $resourceConnection;
    private SourceItemsSaveInterface $sourceItemsSave;
    private SourceItemInterfaceFactory $sourceItemFactory;
    private ProductMediaCategory $productMediaCategory;

    public function __construct(
        MapManager $mapManager,
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepository,
        OrderRepositoryInterface $orderRepository,
        AuditLogger $audit,
        Logger $logger,
        SyncContext $syncContext,
        ResourceConnection $resourceConnection,
        SourceItemsSaveInterface $sourceItemsSave,
        SourceItemInterfaceFactory $sourceItemFactory,
        ProductMediaCategory $productMediaCategory
    ) {
        $this->mapManager = $mapManager;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
        $this->audit = $audit;
        $this->logger = $logger;
        $this->syncContext = $syncContext;
        $this->resourceConnection = $resourceConnection;
        $this->sourceItemsSave = $sourceItemsSave;
        $this->sourceItemFactory = $sourceItemFactory;
        $this->productMediaCategory = $productMediaCategory;
    }

    /**
     * @param array<string, mixed> $envelope
     * @return array{result: string, reason?: string, detail?: string, message?: string}
     */
    public function process(array $envelope): array
    {
        $type = (string)($envelope['entity_type'] ?? '');
        $naturalKey = (string)($envelope['natural_key'] ?? '');
        $correlation = (string)($envelope['correlation_id'] ?? '');
        $incomingChecksum = (string)($envelope['checksum'] ?? '');

        if ($type === '' || $naturalKey === '') {
            return ['result' => 'error', 'message' => 'missing entity_type or natural_key'];
        }

        $map = $this->mapManager->findByNaturalKey($type, $naturalKey, 0);
        $magentoId = ($map !== null) ? $map->getData('magento_id') : null;

        if (!$magentoId) {
            // Shared-key match (SKU / email / increment_id). Magento is the master,
            // so we link to an existing Magento record but never create one from Odoo.
            $magentoId = $this->resolveMagentoByKey($type, $naturalKey);
            if ($magentoId === null) {
                return ['result' => 'unmatched', 'reason' => 'no Magento record with this key (Magento is master; not created)'];
            }
            $linkData = [
                'entity_type' => $type,
                'magento_natural_key' => $naturalKey,
                'magento_id' => $magentoId,
                'odoo_model' => $this->odooModelFor($type),
                'website_id' => 0,
                'sync_status' => EntityMap::STATUS_LINKED,
                'last_correlation_id' => $correlation,
            ];
            if (isset($envelope['odoo_id']) && (int)$envelope['odoo_id'] > 0) {
                $linkData['odoo_id'] = (int)$envelope['odoo_id'];
            }
            $map = $this->mapManager->link($linkData);
        }

        if ($incomingChecksum !== '' && $this->mapManager->isEcho($map, $incomingChecksum, 'odoo')) {
            return ['result' => 'echo', 'reason' => 'matches last synced checksum (our own write)'];
        }

        $start = microtime(true);
        $this->syncContext->setInbound(true);
        try {
            $detail = $this->apply($type, $map, (array)($envelope['payload'] ?? []));
        } catch (\Throwable $e) {
            $this->syncContext->setInbound(false);
            $this->audit->log([
                'correlation_id' => $correlation,
                'entity_type' => $type,
                'magento_id' => (string)$map->getData('magento_id'),
                'odoo_id' => (int)$map->getData('odoo_id'),
                'direction' => EntityMap::DIRECTION_O2M,
                'operation' => (string)($envelope['operation'] ?? 'update'),
                'response_snippet' => $e->getMessage(),
                'result' => 'failed',
                'attempt_no' => 1,
                'duration_ms' => (int)round((microtime(true) - $start) * 1000),
            ]);
            $this->logger->error('Odoo inbound apply failed', ['type' => $type, 'key' => $naturalKey, 'error' => $e->getMessage()]);

            return ['result' => 'failed', 'message' => $e->getMessage()];
        }
        $this->syncContext->setInbound(false);

        if ($incomingChecksum !== '') {
            $map->setData('odoo_checksum', $incomingChecksum);
        }
        $map->setData('last_direction', EntityMap::DIRECTION_O2M);
        $map->setData('last_correlation_id', $correlation);
        $map->setData('sync_status', EntityMap::STATUS_LINKED);
        $this->mapManager->save($map);

        $this->audit->log([
            'correlation_id' => $correlation,
            'entity_type' => $type,
            'magento_id' => (string)$map->getData('magento_id'),
            'odoo_id' => (int)$map->getData('odoo_id'),
            'direction' => EntityMap::DIRECTION_O2M,
            'operation' => (string)($envelope['operation'] ?? 'update'),
            'response_snippet' => $detail,
            'result' => 'success',
            'attempt_no' => 1,
            'duration_ms' => (int)round((microtime(true) - $start) * 1000),
        ]);

        return ['result' => 'applied', 'detail' => $detail];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveMagentoByKey(string $type, string $naturalKey): ?string
    {
        $connection = $this->resourceConnection->getConnection();
        if ($type === self::ENTITY_PRODUCT) {
            $id = $connection->fetchOne(
                'SELECT entity_id FROM ' . $this->resourceConnection->getTableName('catalog_product_entity') . ' WHERE sku = ? LIMIT 1',
                [$naturalKey]
            );
        } elseif ($type === self::ENTITY_INVENTORY) {
            $sku = strpos($naturalKey, ':') !== false ? substr($naturalKey, strpos($naturalKey, ':') + 1) : $naturalKey;
            $id = $connection->fetchOne(
                'SELECT entity_id FROM ' . $this->resourceConnection->getTableName('catalog_product_entity') . ' WHERE sku = ? LIMIT 1',
                [$sku]
            );
        } elseif ($type === self::ENTITY_CUSTOMER) {
            $id = $connection->fetchOne(
                'SELECT entity_id FROM ' . $this->resourceConnection->getTableName('customer_entity') . ' WHERE email = ? ORDER BY entity_id ASC LIMIT 1',
                [$naturalKey]
            );
        } elseif ($type === self::ENTITY_ORDER) {
            $id = $connection->fetchOne(
                'SELECT entity_id FROM ' . $this->resourceConnection->getTableName('sales_order') . ' WHERE increment_id = ? LIMIT 1',
                [$naturalKey]
            );
        } else {
            return null;
        }

        return $id ? (string)$id : null;
    }

    private function odooModelFor(string $type): string
    {
        switch ($type) {
            case self::ENTITY_CUSTOMER:
                return 'res.partner';
            case self::ENTITY_ORDER:
                return 'sale.order';
            case self::ENTITY_INVENTORY:
                return 'product.product';
            case self::ENTITY_PRODUCT:
            default:
                return 'product.template';
        }
    }

    private function apply(string $type, EntityMap $map, array $payload): string
    {
        if ($type === self::ENTITY_PRODUCT) {
            $product = $this->productRepository->getById((int)$map->getData('magento_id'));
            $changed = [];
            if (isset($payload['name']) && (string)$payload['name'] !== '') {
                $product->setName((string)$payload['name']);
                $changed[] = 'name';
            }
            if (isset($payload['price']) && is_numeric($payload['price'])) {
                $product->setPrice((float)$payload['price']);
                $changed[] = 'price';
            }
            if (isset($payload['description']) && (string)$payload['description'] !== '') {
                $product->setCustomAttribute('description', (string)$payload['description']);
                $changed[] = 'description';
            }
            if (isset($payload['status']) && in_array((int)$payload['status'], [1, 2], true)) {
                $product->setStatus((int)$payload['status']);
                $changed[] = 'status';
            }
            if (isset($payload['visibility']) && (int)$payload['visibility'] > 0) {
                $product->setVisibility((int)$payload['visibility']);
                $changed[] = 'visibility';
            }
            if (isset($payload['special_price']) && is_numeric($payload['special_price']) && (float)$payload['special_price'] > 0) {
                $product->setCustomAttribute('special_price', (float)$payload['special_price']);
                $changed[] = 'special_price';
            }
            if (isset($payload['image']) && is_string($payload['image']) && $payload['image'] !== '') {
                if ($this->productMediaCategory->applyImage($product, $payload['image'])) {
                    $changed[] = 'image';
                }
            }
            if (isset($payload['categories']) && $payload['categories'] !== '' && $payload['categories'] !== []) {
                $names = is_array($payload['categories']) ? $payload['categories'] : [$payload['categories']];
                if ($this->productMediaCategory->applyCategories($product, $names) !== []) {
                    $changed[] = 'categories';
                }
            }
            if ($changed) {
                $this->productRepository->save($product);
            }

            return 'product ' . $map->getData('magento_id') . ' updated: ' . (implode(',', $changed) ?: 'no-op');
        }

        if ($type === self::ENTITY_CUSTOMER) {
            $customer = $this->customerRepository->getById((int)$map->getData('magento_id'));
            $changed = [];
            if (isset($payload['name']) && (string)$payload['name'] !== '') {
                $parts = explode(' ', trim((string)$payload['name']), 2);
                $customer->setFirstname($parts[0]);
                $customer->setLastname($parts[1] ?? $parts[0]);
                $changed[] = 'name';
            }
            if ($changed) {
                $this->customerRepository->save($customer);
            }

            return 'customer ' . $map->getData('magento_id') . ' updated: ' . (implode(',', $changed) ?: 'no-op');
        }

        if ($type === self::ENTITY_ORDER) {
            // Orders are Magento-authoritative: inbound is ADDITIVE (status / tracking notes only).
            $order = $this->orderRepository->get((int)$map->getData('magento_id'));
            $notes = [];
            $state = (string)($payload['state'] ?? '');
            if ($state !== '') {
                $notes[] = (string)__('Odoo state: %1', $state);
            }
            $tracking = (string)($payload['tracking'] ?? '');
            if ($tracking !== '') {
                $notes[] = (string)__('Odoo tracking: %1', $tracking);
            }
            if ($notes !== []) {
                foreach ($notes as $note) {
                    $order->addCommentToStatusHistory($note);
                }
                $this->orderRepository->save($order);
            }

            return 'order ' . $map->getData('magento_id') . ' notes: ' . (implode('; ', $notes) ?: 'no-op');
        }

        if ($type === self::ENTITY_INVENTORY) {
            $key = (string)$map->getData('magento_natural_key');
            $sku = strpos($key, ':') !== false ? substr($key, strpos($key, ':') + 1) : $key;
            $source = strpos($key, ':') !== false ? substr($key, 0, strpos($key, ':')) : 'default';
            if (!isset($payload['qty']) || !is_numeric($payload['qty'])) {
                return 'inventory ' . $sku . ' no-op (no qty)';
            }
            $qty = (float)$payload['qty'];
            $sourceItem = $this->sourceItemFactory->create();
            $sourceItem->setSourceCode($source);
            $sourceItem->setSku($sku);
            $sourceItem->setQuantity($qty);
            $sourceItem->setStatus($qty > 0 ? SourceItemInterface::STATUS_IN_STOCK : SourceItemInterface::STATUS_OUT_OF_STOCK);
            $this->sourceItemsSave->execute([$sourceItem]);

            return 'inventory ' . $sku . ' @ ' . $source . ' qty=' . $qty;
        }

        return 'no inbound handler for ' . $type;
    }
}
