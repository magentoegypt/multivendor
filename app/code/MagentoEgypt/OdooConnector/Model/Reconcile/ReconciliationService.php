<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Reconcile;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use MagentoEgypt\OdooConnector\Helper\Config;
use MagentoEgypt\OdooConnector\Model\Queue\QueueManager;
use MagentoEgypt\OdooConnector\Model\Sync\CorrelationId;

/**
 * Reconciliation (architecture section 7): (1) re-drive dead-lettered queue rows,
 * and (2) re-enqueue linked entities per enabled domain so any missed/ drifted
 * M->O change is healed. Enqueue coalescing keeps it from piling up duplicates.
 *
 * NOTE: this is a full-resync safety net; a production refinement is to filter by
 * a Magento updated_at watermark vs the map's last-synced value (incremental).
 */
class ReconciliationService
{
    /** map entity_type => domain config key */
    private const DOMAIN = [
        'product' => 'products',
        'customer_buyer' => 'customers',
        'order' => 'orders',
        'inventory_source_item' => 'inventory',
    ];

    private ResourceConnection $resourceConnection;
    private QueueManager $queue;
    private Config $config;
    private CorrelationId $correlationId;

    public function __construct(
        ResourceConnection $resourceConnection,
        QueueManager $queue,
        Config $config,
        CorrelationId $correlationId
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->queue = $queue;
        $this->correlationId = $correlationId;
        $this->config = $config;
    }

    /**
     * @return array{failed_requeued: int, requeued: array<string, int>}
     */
    public function run(int $perType = 200): array
    {
        $connection = $this->resourceConnection->getConnection();
        $queueTable = $this->resourceConnection->getTableName('magentoegypt_odoo_sync_queue');
        $mapTable = $this->resourceConnection->getTableName('magentoegypt_odoo_entity_map');

        // 1) Re-drive dead-lettered rows.
        $failedRequeued = (int)$connection->update(
            $queueTable,
            ['status' => 'pending', 'attempts' => 0, 'scheduled_at' => new Expression('NOW()'), 'last_error' => null],
            ['status = ?' => 'failed']
        );

        // 2) Re-enqueue linked entities per enabled domain.
        $requeued = [];
        foreach (self::DOMAIN as $entityType => $domainKey) {
            if (!$this->config->isDomainEnabled($domainKey)) {
                continue;
            }
            $rows = $connection->fetchAll(
                "SELECT magento_id, magento_natural_key FROM {$mapTable} "
                . "WHERE entity_type = ? AND sync_status = 'linked' AND magento_id IS NOT NULL "
                . 'ORDER BY updated_at ASC LIMIT ' . (int)$perType,
                [$entityType]
            );
            $count = 0;
            foreach ($rows as $row) {
                // The inventory consumer expects "source:sku" (the natural key) as magento_id.
                $magentoId = $entityType === 'inventory_source_item'
                    ? (string)$row['magento_natural_key']
                    : (string)$row['magento_id'];
                $enqueued = $this->queue->enqueue([
                    'entity_type' => $entityType,
                    'magento_id' => $magentoId,
                    'website_id' => 0,
                    'direction' => 'm2o',
                    'operation' => $entityType === 'inventory_source_item' ? 'stock' : 'update',
                    'correlation_id' => $this->correlationId->generate(),
                ]);
                if ($enqueued) {
                    $count++;
                }
            }
            $requeued[$entityType] = $count;
        }

        // 3) Retention (TC46): nothing else ever removed rows, so both tables grew by
        //    ~19k queue + ~35k log rows a day. Runs with the daily reconcile.
        $pruned = $this->prune($connection, $queueTable);

        return ['failed_requeued' => $failedRequeued, 'requeued' => $requeued, 'pruned' => $pruned];
    }

    /**
     * Delete in small primary-key-ordered batches so no single statement holds locks
     * for long while the every-minute consumer is writing to the same tables.
     *
     * Kept: every pending/processing/failed queue row; `done` rows for 7 days; log rows
     * for 30 days, and failed/retry log rows for 90 days (what someone debugging a
     * sync problem actually reads).
     *
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @return array{queue_done: int, log: int}
     */
    private function prune($connection, string $queueTable): array
    {
        $logTable = $this->resourceConnection->getTableName('magentoegypt_odoo_sync_log');
        $batch = 5000;
        $maxBatches = 400;
        $statements = [
            'queue_done' => "DELETE FROM {$queueTable} WHERE status = 'done'"
                . " AND updated_at < NOW() - INTERVAL 7 DAY ORDER BY queue_id LIMIT {$batch}",
            'log' => "DELETE FROM {$logTable} WHERE created_at < NOW() - INTERVAL 30 DAY"
                . " AND (result NOT IN ('failed', 'retry') OR created_at < NOW() - INTERVAL 90 DAY)"
                . " ORDER BY log_id LIMIT {$batch}",
        ];
        $pruned = [];
        foreach ($statements as $key => $sql) {
            $total = 0;
            for ($i = 0; $i < $maxBatches; $i++) {
                $deleted = $connection->query($sql)->rowCount();
                $total += $deleted;
                if ($deleted < $batch) {
                    break;
                }
                usleep(100000);
            }
            $pruned[$key] = $total;
        }
        return $pruned;
    }
}
