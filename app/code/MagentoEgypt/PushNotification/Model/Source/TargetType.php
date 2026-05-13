<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use MagentoEgypt\PushNotification\Api\Data\NotificationInterface;

class TargetType implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => NotificationInterface::TARGET_TYPE_ALL,            'label' => __('All Users')],
            ['value' => NotificationInterface::TARGET_TYPE_CUSTOMERS,      'label' => __('Customers')],
            ['value' => NotificationInterface::TARGET_TYPE_VENDORS,        'label' => __('Vendors')],
            ['value' => NotificationInterface::TARGET_TYPE_CUSTOMER_GROUP, 'label' => __('Specific Customer Group')],
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            NotificationInterface::TARGET_TYPE_ALL            => __('All Users'),
            NotificationInterface::TARGET_TYPE_CUSTOMERS      => __('Customers'),
            NotificationInterface::TARGET_TYPE_VENDORS        => __('Vendors'),
            NotificationInterface::TARGET_TYPE_CUSTOMER_GROUP => __('Specific Customer Group'),
        ];
    }
}
