<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class EntityMap extends AbstractDb
{
    public const TABLE = 'magentoegypt_odoo_entity_map';

    protected function _construct(): void
    {
        $this->_init(self::TABLE, 'map_id');
    }
}
