<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package One Step Checkout Core for Magento 2
 */

namespace Amasty\CheckoutCore\Model\Field;

use Magento\Framework\App\ResourceConnection;

class DeleteAttributeFrontendLabel
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    public function execute(int $attributeId, int $storeId): void
    {
        $connection = $this->resourceConnection->getConnection();
        $condition = ['attribute_id = ?' => $attributeId, 'store_id = ?' => $storeId];
        $connection->delete($this->resourceConnection->getTableName('eav_attribute_label'), $condition);
    }
}
