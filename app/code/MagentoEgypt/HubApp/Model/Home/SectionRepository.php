<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Model\Home;

use Magento\Framework\App\ResourceConnection;
use MagentoEgypt\HubApp\Model\ResourceModel\Section as SectionResource;

/**
 * Active section rows of a store view (its own and the all-stores ones), in
 * admin order. Schedule and audience are applied in PHP by Schedule, so the
 * same rows also say when the Home changes next.
 */
class SectionRepository
{
    public function __construct(private readonly ResourceConnection $resource)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     * @throws \Throwable when the rows cannot be read (table missing before
     *         setup:upgrade, database unreachable). Never an empty list instead:
     *         an empty Home would be cached and served as if it were meant, while
     *         an error lets the app keep its built-in Home and ask again.
     */
    public function getActiveRows(int $storeId): array
    {
        $connection = $this->resource->getConnection();

        return $connection->fetchAll(
            $connection->select()
                ->from($this->resource->getTableName(SectionResource::TABLE))
                ->where('is_active = ?', 1)
                ->where('store_id IN (?)', [0, $storeId])
                ->order('position ASC')
                ->order('section_id ASC')
        );
    }
}
