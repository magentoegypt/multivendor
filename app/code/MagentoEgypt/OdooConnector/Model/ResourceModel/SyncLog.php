<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SyncLog extends AbstractDb
{
    public const TABLE = 'magentoegypt_odoo_sync_log';

    protected function _construct(): void
    {
        $this->_init(self::TABLE, 'log_id');
    }
}
