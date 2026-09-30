<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Store;

use MagentoEgypt\HubAppVendors\Model\Store\StoreCards;
use PHPUnit\Framework\TestCase;

/**
 * The join date format of HmStoreCard.joined_at (non-null ISO-8601 UTC).
 *
 * @covers \MagentoEgypt\HubAppVendors\Model\Store\StoreCards::isoUtc
 */
class StoreCardsTest extends TestCase
{
    public function testDatabaseTimestampIsIsoUtc(): void
    {
        $this->assertSame('2026-09-28T21:05:07Z', StoreCards::isoUtc('2026-09-28 21:05:07'));
    }

    public function testEmptyOrZeroDateFallsBackToTheEpoch(): void
    {
        $this->assertSame('1970-01-01T00:00:00Z', StoreCards::isoUtc(''));
        $this->assertSame('1970-01-01T00:00:00Z', StoreCards::isoUtc('0000-00-00 00:00:00'));
        $this->assertSame('1970-01-01T00:00:00Z', StoreCards::isoUtc('not a date'));
    }
}
