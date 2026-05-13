<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Model\ResourceModel\Notification;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected $_idFieldName = 'notification_id';

    protected $_eventPrefix = 'magentoegypt_push_notification_collection';

    protected $_eventObject = 'notification_collection';

    protected function _construct()
    {
        $this->_init(
            \MagentoEgypt\PushNotification\Model\Notification::class,
            \MagentoEgypt\PushNotification\Model\ResourceModel\Notification::class
        );
    }
}
