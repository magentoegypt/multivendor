<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Model;

use Magento\Framework\Model\AbstractModel;
use MagentoEgypt\PushNotification\Api\Data\NotificationInterface;

class Notification extends AbstractModel implements NotificationInterface
{
    const CACHE_TAG = 'magentoegypt_push_notification';

    protected $_cacheTag = self::CACHE_TAG;

    protected $_eventPrefix = 'magentoegypt_push_notification';

    protected function _construct()
    {
        $this->_init(\MagentoEgypt\PushNotification\Model\ResourceModel\Notification::class);
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getNotificationId()
    {
        return $this->getData(self::NOTIFICATION_ID);
    }

    public function setNotificationId($id)
    {
        return $this->setData(self::NOTIFICATION_ID, $id);
    }

    public function getTitle()
    {
        return $this->getData(self::TITLE);
    }

    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getMessage()
    {
        return $this->getData(self::MESSAGE);
    }

    public function setMessage($message)
    {
        return $this->setData(self::MESSAGE, $message);
    }

    public function getImage()
    {
        return $this->getData(self::IMAGE);
    }

    public function setImage($image)
    {
        return $this->setData(self::IMAGE, $image);
    }

    public function getActionUrl()
    {
        return $this->getData(self::ACTION_URL);
    }

    public function setActionUrl($url)
    {
        return $this->setData(self::ACTION_URL, $url);
    }

    public function getTargetType()
    {
        return $this->getData(self::TARGET_TYPE);
    }

    public function setTargetType($type)
    {
        return $this->setData(self::TARGET_TYPE, $type);
    }

    public function getTargetIds()
    {
        return $this->getData(self::TARGET_IDS);
    }

    public function setTargetIds($ids)
    {
        return $this->setData(self::TARGET_IDS, $ids);
    }

    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getRecipientsCount()
    {
        return (int) $this->getData(self::RECIPIENTS_COUNT);
    }

    public function setRecipientsCount($count)
    {
        return $this->setData(self::RECIPIENTS_COUNT, $count);
    }

    public function getSuccessCount()
    {
        return (int) $this->getData(self::SUCCESS_COUNT);
    }

    public function setSuccessCount($count)
    {
        return $this->setData(self::SUCCESS_COUNT, $count);
    }

    public function getFailureCount()
    {
        return (int) $this->getData(self::FAILURE_COUNT);
    }

    public function setFailureCount($count)
    {
        return $this->setData(self::FAILURE_COUNT, $count);
    }

    public function getScheduledAt()
    {
        return $this->getData(self::SCHEDULED_AT);
    }

    public function setScheduledAt($datetime)
    {
        return $this->setData(self::SCHEDULED_AT, $datetime);
    }

    public function getSentAt()
    {
        return $this->getData(self::SENT_AT);
    }

    public function setSentAt($datetime)
    {
        return $this->setData(self::SENT_AT, $datetime);
    }

    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setCreatedAt($datetime)
    {
        return $this->setData(self::CREATED_AT, $datetime);
    }

    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt($datetime)
    {
        return $this->setData(self::UPDATED_AT, $datetime);
    }

    public function getCreatedBy()
    {
        return $this->getData(self::CREATED_BY);
    }

    public function setCreatedBy($userId)
    {
        return $this->setData(self::CREATED_BY, $userId);
    }
}
