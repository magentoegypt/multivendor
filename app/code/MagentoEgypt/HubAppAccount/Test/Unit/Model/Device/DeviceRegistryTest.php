<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Test\Unit\Model\Device;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use MagentoEgypt\HubAppAccount\Model\Device\DeviceRegistry;
use MagentoEgypt\SmsExtend\Model\Throttle;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Push devices: a token is bound to the customer of the request (or to nobody), a customer keeps at
 * most ten active devices, new tokens are limited per address, and only the caller's row can be
 * switched off.
 */
final class DeviceRegistryTest extends TestCase
{
    private const TABLE = 'magentoegypt_push_notification_device';
    private const TOKEN = 'fcm_token_abcdefghijklmnopqrstuvwxyz0123456789';
    private const IP = '203.0.113.9';

    /** @var AdapterInterface&MockObject */
    private AdapterInterface $connection;

    /** @var Throttle&MockObject */
    private Throttle $throttle;

    /** @var array<int, array{0: string, 1: mixed}> where() calls of the queries */
    private array $where = [];

    protected function setUp(): void
    {
        $select = $this->createMock(Select::class);
        foreach (['from', 'order', 'limit'] as $method) {
            $select->method($method)->willReturnSelf();
        }
        $select->method('where')->willReturnCallback(function (string $condition, $value = null) use ($select) {
            $this->where[] = [$condition, $value];

            return $select;
        });
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->connection->method('select')->willReturn($select);
        $this->throttle = $this->createMock(Throttle::class);
    }

    public function testASignedInRegistrationBindsTheTokenToThatCustomer(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);
        $this->throttle->method('consume')->willReturn(0);
        $this->connection->method('fetchCol')->willReturn(['31']);
        $this->connection->expects(self::once())->method('insertOnDuplicate')->with(
            self::TABLE,
            self::callback(static function (array $row): bool {
                return $row['device_token'] === self::TOKEN
                    && $row['customer_id'] === 5
                    && $row['is_active'] === 1
                    && $row['platform'] === 'android'
                    //  The seller app's column is never written by the customer app.
                    && !array_key_exists('vendor_id', $row);
            }),
            //  A token already on file changes hands: its customer is overwritten too.
            ['customer_id', 'platform', 'is_active', 'store_id', 'app_version', 'last_seen_at']
        );
        $this->connection->expects(self::never())->method('update');

        self::assertTrue($this->registry()->register(self::TOKEN, 'android', '1.4.0', 5, 1, self::IP));
    }

    public function testAGuestRegistrationUnbindsTheToken(): void
    {
        //  A device that signed out registers again as a guest: it stops getting the customer's pushes.
        $this->connection->method('fetchOne')->willReturn('17');
        $this->connection->expects(self::once())->method('insertOnDuplicate')->with(
            self::TABLE,
            self::callback(static fn (array $row): bool => array_key_exists('customer_id', $row) && $row['customer_id'] === null),
            self::callback(static fn (array $fields): bool => in_array('customer_id', $fields, true))
        );
        $this->connection->expects(self::never())->method('fetchCol');

        self::assertTrue($this->registry()->register(self::TOKEN, 'ios', null, null, 1, self::IP));
    }

    public function testNewTokensAreLimitedPerAddress(): void
    {
        $this->connection->method('fetchOne')->willReturn(false);
        $this->throttle->expects(self::once())->method('consume')
            ->with('device_ip', self::IP, 30, 3600)
            ->willReturn(1200);
        $this->connection->expects(self::never())->method('insertOnDuplicate');

        self::assertFalse($this->registry()->register(self::TOKEN, 'android', null, 5, 1, self::IP));
    }

    public function testRefreshingAKnownTokenIsNotLimited(): void
    {
        $this->connection->method('fetchOne')->willReturn('17');
        $this->throttle->expects(self::never())->method('consume');
        $this->connection->method('fetchCol')->willReturn(['17']);
        $this->connection->expects(self::once())->method('insertOnDuplicate');

        self::assertTrue($this->registry()->register(self::TOKEN, 'android', null, 5, 1, self::IP));
    }

    public function testACustomerKeepsAtMostTenActiveDevices(): void
    {
        $this->connection->method('fetchOne')->willReturn('17');
        //  Most recently seen first: the eleventh and twelfth are switched off.
        $this->connection->method('fetchCol')->willReturn(['12', '11', '10', '9', '8', '7', '6', '5', '4', '3', '2', '1']);
        $this->connection->expects(self::once())->method('update')
            ->with(self::TABLE, ['is_active' => 0], ['device_id IN (?)' => [2, 1]]);

        $this->registry()->register(self::TOKEN, 'android', null, 5, 1, self::IP);

        //  Only this customer's active customer-app devices are counted.
        self::assertContains(['customer_id = ?', 5], $this->where);
        self::assertContains(['is_active = 1', null], $this->where);
        self::assertContains(['vendor_id IS NULL', null], $this->where);
    }

    public function testACustomerSwitchesOffOnlyTheirOwnRow(): void
    {
        $this->connection->expects(self::once())->method('update')
            ->with(self::TABLE, ['is_active' => 0], ['device_token = ?' => self::TOKEN, 'customer_id = ?' => 5]);

        $this->registry()->unregister(self::TOKEN, 5);
    }

    public function testAGuestSwitchesOffOnlyAGuestRow(): void
    {
        $this->connection->expects(self::once())->method('update')
            ->with(self::TABLE, ['is_active' => 0], ['device_token = ?' => self::TOKEN, 'customer_id IS NULL']);

        $this->registry()->unregister(self::TOKEN, null);
    }

    private function registry(): DeviceRegistry
    {
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($this->connection);
        $resource->method('getTableName')->willReturnArgument(0);

        return new DeviceRegistry($resource, $this->throttle, $this->createMock(LoggerInterface::class), 30);
    }
}
