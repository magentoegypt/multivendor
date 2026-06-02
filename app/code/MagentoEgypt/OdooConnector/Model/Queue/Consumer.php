<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Queue;

use MagentoEgypt\OdooConnector\Helper\Config;
use MagentoEgypt\OdooConnector\Logger\Logger;
use MagentoEgypt\OdooConnector\Model\Customer\CustomerPusher;
use MagentoEgypt\OdooConnector\Model\Inventory\InventoryPusher;
use MagentoEgypt\OdooConnector\Model\Log\AuditLogger;
use MagentoEgypt\OdooConnector\Model\Order\OrderPusher;
use MagentoEgypt\OdooConnector\Model\Product\ProductPusher;

/**
 * Drains the sync queue once: claim due rows, dispatch by (entity_type,
 * direction), log each attempt, and retry-with-backoff on failure.
 * Invoked by the cron job and the odoo:queue:run command.
 */
class Consumer
{
    private QueueManager $queue;
    private ProductPusher $productPusher;
    private CustomerPusher $customerPusher;
    private InventoryPusher $inventoryPusher;
    private OrderPusher $orderPusher;
    private AuditLogger $audit;
    private Config $config;
    private Logger $logger;

    public function __construct(
        QueueManager $queue,
        ProductPusher $productPusher,
        CustomerPusher $customerPusher,
        InventoryPusher $inventoryPusher,
        OrderPusher $orderPusher,
        AuditLogger $audit,
        Config $config,
        Logger $logger
    ) {
        $this->queue = $queue;
        $this->productPusher = $productPusher;
        $this->customerPusher = $customerPusher;
        $this->inventoryPusher = $inventoryPusher;
        $this->orderPusher = $orderPusher;
        $this->audit = $audit;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @return array{claimed: int, done: int, failed: int, skipped: int}
     */
    public function run(): array
    {
        $batch = $this->queue->getDue($this->config->getBatchSize());
        $claimed = 0;
        $done = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($batch as $row) {
            $queueId = (int)$row['queue_id'];
            if (!$this->queue->claim($queueId)) {
                continue; // another worker took it
            }
            $claimed++;
            $start = microtime(true);

            try {
                $resultMessage = null;
                $odooId = null;

                if ($row['entity_type'] === 'product' && $row['direction'] === 'm2o') {
                    $result = $this->productPusher->pushById((int)$row['magento_id'], (string)$row['correlation_id']);
                    $odooId = $result['odoo_id'];
                    $resultMessage = sprintf('odoo %s id=%d sku=%s', $result['action'], $result['odoo_id'], $result['sku']);
                } elseif ($row['entity_type'] === 'customer_buyer' && $row['direction'] === 'm2o') {
                    $result = $this->customerPusher->pushById((int)$row['magento_id'], (string)$row['correlation_id']);
                    $odooId = $result['odoo_id'];
                    $resultMessage = sprintf('odoo %s id=%d email=%s', $result['action'], $result['odoo_id'], $result['email']);
                } elseif ($row['entity_type'] === 'inventory_source_item' && $row['direction'] === 'm2o') {
                    $parts = explode(':', (string)$row['magento_id'], 2);
                    $result = $this->inventoryPusher->pushBySourceSku($parts[0] ?? 'default', $parts[1] ?? '', (string)$row['correlation_id']);
                    $odooId = $result['odoo_product_id'] ?? null;
                    $resultMessage = $result['action'] === 'set'
                        ? sprintf('odoo on-hand qty=%s product=%d', $result['qty'], $result['odoo_product_id'] ?? 0)
                        : ('skipped: ' . ($result['reason'] ?? ''));
                } elseif ($row['entity_type'] === 'order' && $row['direction'] === 'm2o') {
                    $result = $this->orderPusher->pushById((int)$row['magento_id'], (string)$row['correlation_id']);
                    $odooId = $result['odoo_id'] ?? null;
                    $resultMessage = sprintf('sale.order %s increment=%s odoo_id=%s', $result['action'], $result['increment_id'], $result['odoo_id'] ?? '-');
                }

                if ($resultMessage !== null) {
                    $this->queue->markDone($queueId);
                    $this->audit->log([
                        'correlation_id' => (string)$row['correlation_id'],
                        'entity_type' => (string)$row['entity_type'],
                        'magento_id' => (string)$row['magento_id'],
                        'odoo_id' => $odooId,
                        'website_id' => (int)$row['website_id'],
                        'direction' => (string)$row['direction'],
                        'operation' => (string)$row['operation'],
                        'response_snippet' => $resultMessage,
                        'result' => 'success',
                        'attempt_no' => (int)$row['attempts'] + 1,
                        'duration_ms' => (int)round((microtime(true) - $start) * 1000),
                    ]);
                    $done++;
                } else {
                    $this->queue->markSkipped($queueId, 'No handler for ' . $row['entity_type'] . '/' . $row['direction']);
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $this->queue->markFailureOrRetry($queueId, (int)$row['attempts'], (int)$row['max_attempts'], $e->getMessage());
                $this->audit->log([
                    'correlation_id' => (string)$row['correlation_id'],
                    'entity_type' => (string)$row['entity_type'],
                    'magento_id' => (string)$row['magento_id'],
                    'website_id' => (int)$row['website_id'],
                    'direction' => (string)$row['direction'],
                    'operation' => (string)$row['operation'],
                    'response_snippet' => $e->getMessage(),
                    'result' => 'retry',
                    'attempt_no' => (int)$row['attempts'] + 1,
                    'duration_ms' => (int)round((microtime(true) - $start) * 1000),
                ]);
                $this->logger->error('Odoo sync queue item failed', ['queue_id' => $queueId, 'error' => $e->getMessage()]);
                $failed++;
            }
        }

        return ['claimed' => $claimed, 'done' => $done, 'failed' => $failed, 'skipped' => $skipped];
    }
}
