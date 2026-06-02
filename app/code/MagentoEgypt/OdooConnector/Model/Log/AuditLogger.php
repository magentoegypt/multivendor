<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\Log;

use Magento\Framework\App\ResourceConnection;

/**
 * Append-only writer for magentoegypt_odoo_sync_log (one row per attempt).
 */
class AuditLogger
{
    public const TABLE = 'magentoegypt_odoo_sync_log';

    private ResourceConnection $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function log(array $data): void
    {
        foreach (['request_snippet', 'response_snippet'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = mb_substr($data[$field], 0, 2000);
            }
        }

        $connection = $this->resourceConnection->getConnection();
        $connection->insert($this->resourceConnection->getTableName(self::TABLE), $data);
    }
}
