<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home;

use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\HubApp\Model\ResourceModel\Section as SectionResource;
use Psr\Log\LoggerInterface;

/**
 * Active section rows of a store view (its own and the all-stores ones), in
 * admin order. Schedule and audience are applied in PHP by Schedule, so the
 * same rows also say when the Home changes next.
 */
class SectionRepository
{
    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getActiveRows(int $storeId): array
    {
        try {
            $connection = $this->resource->getConnection();

            return $connection->fetchAll(
                $connection->select()
                    ->from($this->resource->getTableName(SectionResource::TABLE))
                    ->where('is_active = ?', 1)
                    ->where('store_id IN (?)', [0, $storeId])
                    ->order('position ASC')
                    ->order('section_id ASC')
            );
        } catch (\Throwable $e) {
            //  Table missing (module enabled before setup:upgrade): an empty Home,
            //  and the app keeps its built-in fallback.
            $this->logger->error('HubApp: Home sections unavailable: ' . $e->getMessage());

            return [];
        }
    }
}
