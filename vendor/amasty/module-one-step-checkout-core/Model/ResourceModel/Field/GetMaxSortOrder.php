<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\ResourceModel\Field;

use Amasty\CheckoutCore\Model\Field;
use Amasty\CheckoutCore\Model\ResourceModel\Field as FieldResource;
use Magento\Framework\App\ResourceConnection;

class GetMaxSortOrder
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function execute(): int
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                $this->resourceConnection->getTableName(FieldResource::MAIN_TABLE),
                sprintf('MAX(%s)', $connection->quoteIdentifier(Field::SORT_ORDER))
            );

        $select->where(Field::STORE_ID . ' = ?', Field::DEFAULT_STORE_ID);
        $select->where(Field::ENABLED . ' = ?', true);

        $result = $connection->fetchOne($select);
        return !empty($result) ? (int) $result : 0;
    }
}
