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

        return ['failed_requeued' => $failedRequeued, 'requeued' => $requeued];
    }
}
