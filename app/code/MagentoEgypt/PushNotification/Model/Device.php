<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Model;

use Magento\Framework\Model\AbstractModel;

class Device extends AbstractModel
{
    const PLATFORM_ANDROID = 'android';
    const PLATFORM_IOS     = 'ios';
    const PLATFORM_WEB     = 'web';

    protected $_eventPrefix = 'magentoegypt_push_notification_device';

    protected function _construct()
    {
        $this->_init(\MagentoEgypt\PushNotification\Model\ResourceModel\Device::class);
    }
}
