<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SyncQueue extends AbstractDb
{
    public const TABLE = 'magentoegypt_odoo_sync_queue';

    protected function _construct(): void
    {
        $this->_init(self::TABLE, 'queue_id');
    }
}
