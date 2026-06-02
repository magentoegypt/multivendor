<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model;

use Magento\Framework\Model\AbstractModel;
use MagentoEgypt\OdooConnector\Model\ResourceModel\EntityMap as EntityMapResource;

/**
 * One row links a Magento record to its Odoo counterpart, per scope.
 */
class EntityMap extends AbstractModel
{
    public const STATUS_LINKED = 'linked';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFLICT = 'conflict';
    public const STATUS_ERROR = 'error';

    public const DIRECTION_M2O = 'm2o';
    public const DIRECTION_O2M = 'o2m';

    protected function _construct(): void
    {
        $this->_init(EntityMapResource::class);
    }
}
