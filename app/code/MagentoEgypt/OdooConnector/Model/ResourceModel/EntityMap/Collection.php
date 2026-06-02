<?php

declare(strict_types=1);

namespace MagentoEgypt\OdooConnector\Model\ResourceModel\EntityMap;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use MagentoEgypt\OdooConnector\Model\EntityMap;
use MagentoEgypt\OdooConnector\Model\ResourceModel\EntityMap as EntityMapResource;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(EntityMap::class, EntityMapResource::class);
    }
}
