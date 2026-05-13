<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use MagentoEgypt\PushNotification\Api\Data\NotificationInterface;

class Status implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => NotificationInterface::STATUS_DRAFT,     'label' => __('Draft')],
            ['value' => NotificationInterface::STATUS_SCHEDULED, 'label' => __('Scheduled')],
            ['value' => NotificationInterface::STATUS_SENT,      'label' => __('Sent')],
            ['value' => NotificationInterface::STATUS_FAILED,    'label' => __('Failed')],
        ];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            NotificationInterface::STATUS_DRAFT     => __('Draft'),
            NotificationInterface::STATUS_SCHEDULED => __('Scheduled'),
            NotificationInterface::STATUS_SENT      => __('Sent'),
            NotificationInterface::STATUS_FAILED    => __('Failed'),
        ];
    }
}
