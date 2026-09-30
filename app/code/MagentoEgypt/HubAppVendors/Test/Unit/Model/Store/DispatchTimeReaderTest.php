<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Store;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use MagentoEgypt\HubAppVendors\Model\Store\DispatchTimeReader;
use MagentoEgypt\HubAppVendors\Model\Store\VendorAttributeReader;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Measured dispatch times: one query for every seller, kept in the app cache.
 */
final class DispatchTimeReaderTest extends TestCase
{
    public function testMeasuresEverySellerOnceAndCachesTheResult(): void
    {
        $wheres = [];
        $select = $this->createMock(Select::class);
        foreach (['from', 'join', 'group'] as $method) {
            $select->method($method)->willReturnSelf();
        }
        $select->method('where')->willReturnCallback(function (string $condition, $value = null) use (&$wheres, $select) {
            $wheres[] = [$condition, $value];

            return $select;
        });
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->expects(self::once())->method('fetchAll')->willReturn([
            ['vendor_id' => '5', 'shipments' => '2', 'avg_hours' => '30.5'],
            ['vendor_id' => '6', 'shipments' => '1', 'avg_hours' => '10'],
            ['vendor_id' => '7', 'shipments' => '3', 'avg_hours' => '10'],
        ]);

        $cache = $this->createMock(AppCache::class);
        $cache->method('load')->willReturn(null);
        $cache->expects(self::once())->method('save')->with('seller_dispatch_measured', [5 => 2, 7 => 1], ['hm_vendor'], AppCache::MAX_TTL);

        $reader = $this->reader($connection, $cache);
        $first = $reader->forVendors([5, 6, 8], 1);
        $second = $reader->forVendors([7], 1);

        self::assertSame([['pe.vendor_id > ?', 0]], $wheres, 'every seller, not only the ones asked for');
        self::assertSame(['code' => 'next_day', 'label' => 'Next business day', 'source' => 'DECLARED'], $first[8]);
        self::assertSame(['code' => 'measured_2', 'label' => '~2 days', 'source' => 'MEASURED'], $first[5]);
        self::assertArrayNotHasKey(6, $first, 'one shipment is not a measure');
        self::assertSame(['code' => 'measured_1', 'label' => '24h', 'source' => 'MEASURED'], $second[7]);
    }

    public function testACachedMeasureRunsNoQuery(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $connection->expects(self::never())->method('fetchAll');
        $cache = $this->createMock(AppCache::class);
        $cache->method('load')->willReturn(['5' => 3]);
        $cache->expects(self::never())->method('save');

        $out = $this->reader($connection, $cache)->forVendors([5], 1);

        self::assertSame(['code' => 'measured_3', 'label' => '~3 days', 'source' => 'MEASURED'], $out[5]);
    }

    private function reader(AdapterInterface $connection, AppCache $cache): DispatchTimeReader
    {
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);
        $attributes = $this->createMock(VendorAttributeReader::class);
        $attributes->method('values')->willReturnCallback(
            static fn (string $code, array $ids): array => array_intersect_key([8 => 'next_day'], array_flip($ids))
        );
        $emulation = $this->createMock(StorefrontEmulationInterface::class);
        $emulation->method('run')->willReturnCallback(static fn (int $storeId, callable $callback) => $callback());

        return new DispatchTimeReader($resource, $attributes, $emulation, $cache, $this->createMock(LoggerInterface::class));
    }
}
