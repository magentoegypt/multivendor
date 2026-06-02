<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger as MonologLogger;

/**
 * Writes the Odoo connector channel to var/log/odoo_sync.log.
 */
class Handler extends Base
{
    /**
     * @var int
     */
    protected $loggerType = MonologLogger::DEBUG;

    /**
     * @var string
     */
    protected $fileName = '/var/log/odoo_sync.log';
}
