<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Test\Unit\Model\Ranking;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use MagentoEgypt\HomeSections\Model\Ranking\DealRanker;
use PHPUnit\Framework\TestCase;

/**
 * The deals countdown ends at 23:59:59 store time of the soonest special_to_date shown;
 * a bundle's special price is the percent paid, not an amount.
 */
final class DealRankerTest extends TestCase
{
    public function testEndOfDayInTheStoreTimezone(): void
    {
        self::assertSame('2026-09-09T23:59:59+03:00', DealRanker::endOfDay('2026-09-09 00:00:00', 'Asia/Riyadh'));
        self::assertSame('2026-09-09T23:59:59+00:00', DealRanker::endOfDay('2026-09-09', 'UTC'));
        self::assertNull(DealRanker::endOfDay('soon', 'Asia/Riyadh'));
    }

    public function testCountdownUsesTheSoonestShownOffer(): void
    {
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('getConfigTimezone')->willReturn('Asia/Riyadh');
        $ranker = new DealRanker(
            $this->createMock(ResourceConnection::class),
            $this->createMock(EavConfig::class),
            $timezone
        );

        self::assertSame('2026-09-10T23:59:59+03:00', $ranker->countdown([
            ['id' => 1, 'to_date' => '2026-09-12 00:00:00'],
            ['id' => 2, 'to_date' => null],
            ['id' => 3, 'to_date' => '2026-09-10 00:00:00'],
        ], 1));
        self::assertNull($ranker->countdown([['id' => 1, 'to_date' => null]], 1), 'offers without an end have no countdown');
    }

    public function testBundleSpecialPriceIsThePercentPaid(): void
    {
        //  500 at 90: 10% off for a bundle (not 82%), 82% off for a simple product.
        self::assertSame(10.0, DealRanker::percentOff('bundle', 500.0, 90.0));
        self::assertSame(20.0, DealRanker::percentOff('new_bundle', 0.0, 80.0), 'a dynamic-price bundle has no price');
        self::assertSame(82.0, DealRanker::percentOff('simple', 500.0, 90.0));
        self::assertSame(25.0, DealRanker::percentOff('configurable', 100.0, 75.0));

        self::assertNull(DealRanker::percentOff('bundle', 500.0, 100.0), '100% of the price is no deal');
        self::assertNull(DealRanker::percentOff('new_bundle', 500.0, 0.0));
        self::assertNull(DealRanker::percentOff('simple', 90.0, 90.0));
        self::assertNull(DealRanker::percentOff('simple', 80.0, 90.0));
    }

    public function testRankAppliesThePercentRuleToBundlesInSql(): void
    {
        $wheres = [];
        $orders = [];
        $select = $this->createMock(Select::class);
        foreach (['from', 'joinLeft', 'columns', 'limit'] as $method) {
            $select->method($method)->willReturnSelf();
        }
        $select->method('where')->willReturnCallback(function ($condition) use (&$wheres, $select) {
            $wheres[] = (string) $condition;

            return $select;
        });
        $select->method('order')->willReturnCallback(function ($spec) use (&$orders, $select) {
            $orders[] = (string) $spec;

            return $select;
        });

        $connection = $this->createMock(AdapterInterface::class);
        $connection->method('select')->willReturn($select);
        $connection->method('fetchAll')->willReturn([
            ['id' => '8', 'type_id' => 'simple', 'price' => '100.000000', 'special' => '75.000000', 'to_date' => '2026-10-01 00:00:00'],
            ['id' => '7', 'type_id' => 'bundle', 'price' => '500.000000', 'special' => '90.000000', 'to_date' => null],
        ]);
        $resource = $this->createMock(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($connection);
        $resource->method('getTableName')->willReturnArgument(0);

        $ids = ['special_price' => 76, 'price' => 77, 'special_from_date' => 78, 'special_to_date' => 79];
        $eav = $this->createMock(EavConfig::class);
        $eav->method('getAttribute')->willReturnCallback(function ($entity, string $code) use ($ids) {
            $attribute = $this->createMock(AbstractAttribute::class);
            $attribute->method('getId')->willReturn($ids[$code] ?? null);

            return $attribute;
        });
        $timezone = $this->createMock(TimezoneInterface::class);
        $timezone->method('getConfigTimezone')->willReturn('Asia/Riyadh');

        $rows = (new DealRanker($resource, $eav, $timezone))->rank(1, 10);

        $bundleRule = "(e.type_id IN ('bundle', 'new_bundle') AND COALESCE(sp_s.value, sp_d.value) < 100)"
            . " OR (e.type_id NOT IN ('bundle', 'new_bundle') AND COALESCE(p_s.value, p_d.value) > COALESCE(sp_s.value, sp_d.value))";
        self::assertContains($bundleRule, $wheres);
        self::assertStringStartsWith(
            "CASE WHEN e.type_id IN ('bundle', 'new_bundle') THEN 100 - (COALESCE(sp_s.value, sp_d.value))",
            $orders[0]
        );
        self::assertStringEndsWith(' END DESC', $orders[0]);

        self::assertSame([
            ['id' => 8, 'type_id' => 'simple', 'price' => 100.0, 'special' => 75.0, 'percent_off' => 25.0, 'to_date' => '2026-10-01 00:00:00'],
            ['id' => 7, 'type_id' => 'bundle', 'price' => 500.0, 'special' => 90.0, 'percent_off' => 10.0, 'to_date' => null],
        ], $rows);
    }
}
