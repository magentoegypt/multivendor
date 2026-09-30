<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Seller;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubApp\Model\Cache\AppCache;
use MagentoEgypt\HubApp\Model\Seller\SellerDirectory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Seller names: one storefront emulation per store view on an app-cache miss, none on a hit.
 */
final class SellerDirectoryTest extends TestCase
{
    public function testAMissBuildsEveryNameInOneEmulationAndCachesThem(): void
    {
        $cache = $this->createMock(AppCache::class);
        $cache->method('load')->willReturn(null);
        $cache->expects(self::once())->method('save')
            ->with('seller_names_2', [3 => 'Enara', 4 => 'Ronza Store'], ['hm_vendor'], AppCache::MAX_TTL);
        $emulation = $this->createMock(StorefrontEmulationInterface::class);
        $emulation->expects(self::once())->method('run')
            ->willReturnCallback(static fn (int $storeId, callable $callback) => $callback());

        $directory = $this->directory($cache, $emulation, true);

        self::assertSame([3 => 'Enara'], $directory->names([3, 99], 2));
        self::assertSame([4 => 'Ronza Store'], $directory->names([4], 2), 'same request: no second emulation');
    }

    public function testAHitNeedsNoEmulationAndNoQuery(): void
    {
        $cache = $this->createMock(AppCache::class);
        $cache->method('load')->with('seller_names_1')->willReturn(['3' => 'إنارة']);
        $cache->expects(self::never())->method('save');
        $emulation = $this->createMock(StorefrontEmulationInterface::class);
        $emulation->expects(self::never())->method('run');

        self::assertSame([3 => 'إنارة'], $this->directory($cache, $emulation, false)->names([3], 1));
    }

    /**
     * Seller codes chosen for a section (the seeded Featured Stores among them) that match no seller
     * are dropped; the others match in any case and keep their order.
     */
    public function testCodesOfSellersThatDoNotExistAreDropped(): void
    {
        $directory = $this->directory(
            $this->createMock(AppCache::class),
            $this->createMock(StorefrontEmulationInterface::class),
            true
        );

        self::assertSame([4, 3], $directory->approvedIdsForCodes(['RONZA', 'loly', 'enara', 'MIA', 'ronza']));
        self::assertSame([], $directory->approvedIdsForCodes(['loly', 'MIA', '']));
    }

    private function directory(AppCache $cache, StorefrontEmulationInterface $emulation, bool $queried): SellerDirectory
    {
        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('order')->willReturnSelf();
        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->expects($queried ? self::once() : self::never())->method('fetchAll')->willReturn([
            ['entity_id' => '3', 'vendor_id' => 'ENARA', 'company' => 'Enara', 'status' => '2', 'created_at' => '2026-01-01 00:00:00'],
            ['entity_id' => '4', 'vendor_id' => 'ronza', 'company' => 'Ronza Store', 'status' => '2', 'created_at' => '2026-02-01 00:00:00'],
        ]);
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        return new SellerDirectory($resource, $emulation, $cache, $this->createMock(LoggerInterface::class));
    }
}
