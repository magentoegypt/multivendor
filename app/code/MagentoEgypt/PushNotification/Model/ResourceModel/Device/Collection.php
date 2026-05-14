<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Model\ResourceModel\Device;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'device_id';

    protected function _construct()
    {
        $this->_init(
            \MagentoEgypt\PushNotification\Model\Device::class,
            \MagentoEgypt\PushNotification\Model\ResourceModel\Device::class
        );
    }
}
