<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Api\Data;

interface NotificationInterface
{
    const NOTIFICATION_ID    = 'notification_id';
    const TITLE              = 'title';
    const MESSAGE            = 'message';
    const IMAGE              = 'image';
    const ACTION_URL         = 'action_url';
    const TARGET_TYPE        = 'target_type';
    const TARGET_IDS         = 'target_ids';
    const STATUS             = 'status';
    const RECIPIENTS_COUNT   = 'recipients_count';
    const SUCCESS_COUNT      = 'success_count';
    const FAILURE_COUNT      = 'failure_count';
    const SCHEDULED_AT       = 'scheduled_at';
    const SENT_AT            = 'sent_at';
    const CREATED_AT         = 'created_at';
    const UPDATED_AT         = 'updated_at';
    const CREATED_BY         = 'created_by';

    const STATUS_DRAFT     = 0;
    const STATUS_SCHEDULED = 1;
    const STATUS_SENT      = 2;
    const STATUS_FAILED    = 3;

    const TARGET_TYPE_ALL            = 'all';
    const TARGET_TYPE_CUSTOMERS      = 'customers';
    const TARGET_TYPE_VENDORS        = 'vendors';
    const TARGET_TYPE_CUSTOMER_GROUP = 'customer_group';

    /**
     * @return int|null
     */
    public function getNotificationId();

    /**
     * @param int $id
     * @return $this
     */
    public function setNotificationId($id);

    /**
     * @return string|null
     */
    public function getTitle();

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title);

    /**
     * @return string|null
     */
    public function getMessage();

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message);

    /**
     * @return string|null
     */
    public function getImage();

    /**
     * @param string|null $image
     * @return $this
     */
    public function setImage($image);

    /**
     * @return string|null
     */
    public function getActionUrl();

    /**
     * @param string|null $url
     * @return $this
     */
    public function setActionUrl($url);

    /**
     * @return string|null
     */
    public function getTargetType();

    /**
     * @param string $type
     * @return $this
     */
    public function setTargetType($type);

    /**
     * @return string|null
     */
    public function getTargetIds();

    /**
     * @param string|null $ids
     * @return $this
     */
    public function setTargetIds($ids);

    /**
     * @return int|null
     */
    public function getStatus();

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * @return int
     */
    public function getRecipientsCount();

    /**
     * @param int $count
     * @return $this
     */
    public function setRecipientsCount($count);

    /**
     * @return int
     */
    public function getSuccessCount();

    /**
     * @param int $count
     * @return $this
     */
    public function setSuccessCount($count);

    /**
     * @return int
     */
    public function getFailureCount();

    /**
     * @param int $count
     * @return $this
     */
    public function setFailureCount($count);

    /**
     * @return string|null
     */
    public function getScheduledAt();

    /**
     * @param string|null $datetime
     * @return $this
     */
    public function setScheduledAt($datetime);

    /**
     * @return string|null
     */
    public function getSentAt();

    /**
     * @param string|null $datetime
     * @return $this
     */
    public function setSentAt($datetime);

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @param string $datetime
     * @return $this
     */
    public function setCreatedAt($datetime);

    /**
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * @param string $datetime
     * @return $this
     */
    public function setUpdatedAt($datetime);

    /**
     * @return int|null
     */
    public function getCreatedBy();

    /**
     * @param int|null $userId
     * @return $this
     */
    public function setCreatedBy($userId);
}
