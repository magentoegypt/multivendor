<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\PushNotification\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Device extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('magentoegypt_push_notification_device', 'device_id');
    }

    /**
     * Mark a single device token as inactive (FCM reported permanent failure).
     *
     * @param string $token
     * @return int rows affected
     */
    public function deactivateToken(string $token): int
    {
        $connection = $this->getConnection();
        return (int) $connection->update(
            $this->getMainTable(),
            ['is_active' => 0, 'updated_at' => $connection->formatDate(true)],
            ['device_token = ?' => $token]
        );
    }
}
