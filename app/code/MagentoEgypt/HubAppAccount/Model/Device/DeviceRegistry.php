<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Device;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use MagentoEgypt\HubAppAccount\Model\Otp\SendThrottle;
use Psr\Log\LoggerInterface;

/**
 * The customer app's FCM tokens in MagentoEgypt_PushNotification's device table, which the admin's
 * push notifications are sent to (Service\Recipient\Resolver: active rows, by customer or group).
 *
 * register(): one row per token (unique index), upserted: bound to the customer when the request
 * carries a customer token, a guest row (customer_id NULL) otherwise, so a device that signs out and
 * registers again stops receiving that customer's pushes. customer_id comes only from the token
 * context, never from input; vendor_id (the seller app's) is never written. store_id, app_version and
 * last_seen_at are this module's columns.
 *
 * Abuse limits: an unknown token counts against a per-IP hourly limit (new rows only; refreshing a
 * known token is not limited), and a customer keeps at most MAX_ACTIVE_PER_CUSTOMER active devices,
 * the least recently seen switched off first. The limit's IP is Magento's RemoteAddress: behind a CDN
 * that needs the real-client header configured, or every visitor shares the CDN's addresses.
 *
 * unregister(): switches off the caller's row for the token (the customer's, or a guest row for a
 * guest), so nobody can switch off someone else's device. Both are idempotent.
 */
class DeviceRegistry
{
    public const MAX_ACTIVE_PER_CUSTOMER = 10;

    private const TABLE = 'magentoegypt_push_notification_device';
    private const THROTTLE_BUCKET = 'device_ip';

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly SendThrottle $throttle,
        private readonly LoggerInterface $logger,
        private readonly int $newTokensPerIpHour = 30
    ) {
    }

    /**
     * @return bool false when an unknown token was refused by the per-IP limit
     */
    public function register(
        string $token,
        string $platform,
        ?string $appVersion,
        ?int $customerId,
        int $storeId,
        string $clientIp
    ): bool {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName(self::TABLE);

        $known = (bool) $connection->fetchOne(
            $connection->select()->from($table, ['device_id'])->where('device_token = ?', $token)->limit(1)
        );
        if (!$known && $this->throttle->consume(self::THROTTLE_BUCKET, $clientIp, $this->newTokensPerIpHour, 3600) > 0) {
            $this->logger->info('HubAppAccount: device token not registered, too many new tokens from one address.');

            return false;
        }

        $connection->insertOnDuplicate(
            $table,
            [
                'device_token' => $token,
                'customer_id' => $customerId,
                'platform' => $platform,
                'is_active' => 1,
                'store_id' => $storeId > 0 ? $storeId : null,
                'app_version' => $appVersion,
                'last_seen_at' => gmdate('Y-m-d H:i:s'),
            ],
            ['customer_id', 'platform', 'is_active', 'store_id', 'app_version', 'last_seen_at']
        );
        if ($customerId !== null) {
            $this->capActiveDevices($customerId);
        }

        return true;
    }

    public function unregister(string $token, ?int $customerId): void
    {
        $where = ['device_token = ?' => $token];
        if ($customerId === null) {
            $where[] = 'customer_id IS NULL';
        } else {
            $where['customer_id = ?'] = $customerId;
        }
        $this->resource->getConnection()->update(
            $this->resource->getTableName(self::TABLE),
            ['is_active' => 0],
            $where
        );
    }

    /**
     * Keep the customer's most recently seen app devices active, switch the rest off.
     */
    private function capActiveDevices(int $customerId): void
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName(self::TABLE);
        $ids = $connection->fetchCol(
            $connection->select()
                ->from($table, ['device_id'])
                ->where('customer_id = ?', $customerId)
                ->where('is_active = 1')
                ->where('vendor_id IS NULL')
                ->order([new Expression('last_seen_at IS NULL'), 'last_seen_at DESC', 'device_id DESC'])
        );
        $extra = array_slice(array_map('intval', $ids), self::MAX_ACTIVE_PER_CUSTOMER);
        if ($extra) {
            $connection->update($table, ['is_active' => 0], ['device_id IN (?)' => $extra]);
        }
    }
}
