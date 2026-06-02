<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Queue;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use MagentoEgypt\OdooConnector\Helper\Config;
use MagentoEgypt\OdooConnector\Model\Sync\SyncContext;

/**
 * Owns the sync queue: enqueue (with coalescing), atomic claim, and
 * completion / retry-with-backoff. All timing uses SQL NOW() to avoid
 * PHP/MySQL timezone drift.
 */
class QueueManager
{
    public const TABLE = 'magentoegypt_odoo_sync_queue';

    private ResourceConnection $resourceConnection;
    private Config $config;
    private SyncContext $syncContext;

    public function __construct(
        ResourceConnection $resourceConnection,
        Config $config,
        SyncContext $syncContext
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->config = $config;
        $this->syncContext = $syncContext;
    }

    /**
     * Insert a pending row. Coalesces: if an identical pending row already
     * exists (same entity_type + magento_id + operation), it is not duplicated.
     *
     * @param array<string, mixed> $data
     */
    public function enqueue(array $data): bool
    {
        // Don't re-enqueue a change that we are applying from an inbound (O->M) sync.
        if ($this->syncContext->isInbound()) {
            return false;
        }

        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::TABLE);

        $existing = $connection->fetchOne(
            "SELECT queue_id FROM {$table} WHERE entity_type = ? AND magento_id = ? AND operation = ? AND status = 'pending' LIMIT 1",
            [$data['entity_type'], $data['magento_id'] ?? '', $data['operation']]
        );
        if ($existing) {
            return false;
        }

        $row = array_merge([
            'website_id' => 0,
            'vendor_id' => 0,
            'status' => 'pending',
            'attempts' => 0,
            'max_attempts' => $this->config->getMaxAttempts(),
            'priority' => 0,
            'scheduled_at' => new Expression('NOW()'),
        ], $data);

        $connection->insert($table, $row);

        return true;
    }

    /**
     * Due pending rows, highest priority / oldest first.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getDue(int $limit): array
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::TABLE);

        return $connection->fetchAll(
            "SELECT * FROM {$table} WHERE status = 'pending' AND scheduled_at <= NOW() "
            . 'ORDER BY priority DESC, queue_id ASC LIMIT ' . (int)$limit
        );
    }

    /**
     * Optimistic claim: only succeeds if the row is still pending.
     */
    public function claim(int $queueId): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::TABLE);

        $affected = $connection->update(
            $table,
            ['status' => 'processing', 'locked_at' => new Expression('NOW()')],
            ['queue_id = ?' => $queueId, 'status = ?' => 'pending']
        );

        return $affected > 0;
    }

    public function markDone(int $queueId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->update(
            $this->resourceConnection->getTableName(self::TABLE),
            ['status' => 'done', 'last_error' => null],
            ['queue_id = ?' => $queueId]
        );
    }

    public function markSkipped(int $queueId, string $reason): void
    {
        $connection = $this->resourceConnection->getConnection();
        $connection->update(
            $this->resourceConnection->getTableName(self::TABLE),
            ['status' => 'skipped', 'last_error' => mb_substr($reason, 0, 1000)],
            ['queue_id = ?' => $queueId]
        );
    }

    /**
     * Failure handling: re-queue with exponential backoff until max_attempts,
     * then dead-letter as 'failed'.
     */
    public function markFailureOrRetry(int $queueId, int $attempts, int $maxAttempts, string $error): void
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName(self::TABLE);
        $attempts++;

        if ($attempts >= $maxAttempts) {
            $connection->update(
                $table,
                ['status' => 'failed', 'attempts' => $attempts, 'last_error' => mb_substr($error, 0, 1000)],
                ['queue_id = ?' => $queueId]
            );

            return;
        }

        $backoff = $this->config->getBackoffBaseMinutes() * (2 ** ($attempts - 1));
        $connection->update(
            $table,
            [
                'status' => 'pending',
                'attempts' => $attempts,
                'last_error' => mb_substr($error, 0, 1000),
                'scheduled_at' => new Expression('DATE_ADD(NOW(), INTERVAL ' . (int)$backoff . ' MINUTE)'),
            ],
            ['queue_id = ?' => $queueId]
        );
    }
}
