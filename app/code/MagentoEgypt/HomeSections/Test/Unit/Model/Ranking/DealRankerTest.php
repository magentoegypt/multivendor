<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HomeSections\Test\Unit\Model\Ranking;

use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use MagentoEgypt\HomeSections\Model\Ranking\DealRanker;
use PHPUnit\Framework\TestCase;

/**
 * The deals countdown ends at 23:59:59 store time of the soonest special_to_date shown.
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
}
