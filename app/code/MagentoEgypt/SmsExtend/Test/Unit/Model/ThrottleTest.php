<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SmsExtend\Test\Unit\Model;

use MagentoEgypt\SmsExtend\Model\Throttle;
use MagentoEgypt\SmsExtend\Test\Unit\MemoryCache;
use PHPUnit\Framework\TestCase;

/**
 * The fixed-window counters (moved here from MagentoEgypt_HubAppAccount's SendThrottle, which the app's
 * device registration used; SmsExtend's OTP limits need them too, and SmsExtend depends on no HubApp module).
 */
class ThrottleTest extends TestCase
{
    private const HOUR = 3600;

    /** 2026-09-29 12:30:00 UTC, 1800 s into its hour window. */
    private const NOON = 1790683200 + 1800;

    private MemoryCache $cache;

    private Throttle $throttle;

    protected function setUp(): void
    {
        $this->cache = new MemoryCache();
        $this->throttle = new Throttle($this->cache);
    }

    public function testAllowsUpToTheLimitThenSaysHowLongToWait(): void
    {
        for ($i = 0; $i < 5; $i++) {
            self::assertSame(0, $this->throttle->consume('otp_number', '971501234567', 5, self::HOUR, self::NOON));
        }
        $wait = $this->throttle->consume('otp_number', '971501234567', 5, self::HOUR, self::NOON);
        self::assertSame(self::HOUR - (self::NOON % self::HOUR), $wait);
    }

    public function testSubjectsAndBucketsAreCountedApart(): void
    {
        self::assertSame(0, $this->throttle->consume('otp_number', 'a', 1, self::HOUR, self::NOON));
        self::assertSame(0, $this->throttle->consume('otp_number', 'b', 1, self::HOUR, self::NOON));
        self::assertSame(0, $this->throttle->consume('otp_ip', 'a', 1, self::HOUR, self::NOON));
        self::assertGreaterThan(0, $this->throttle->consume('otp_number', 'a', 1, self::HOUR, self::NOON));
    }

    public function testNextWindowStartsAfresh(): void
    {
        self::assertSame(0, $this->throttle->consume('otp_ip', '10.0.0.1', 1, self::HOUR, self::NOON));
        self::assertGreaterThan(0, $this->throttle->consume('otp_ip', '10.0.0.1', 1, self::HOUR, self::NOON + 1));
        self::assertSame(0, $this->throttle->consume('otp_ip', '10.0.0.1', 1, self::HOUR, self::NOON + self::HOUR));
    }

    public function testZeroLimitIsOff(): void
    {
        for ($i = 0; $i < 20; $i++) {
            self::assertSame(0, $this->throttle->consume('device_ip', '10.0.0.1', 0, self::HOUR, self::NOON));
        }
        self::assertSame([], $this->cache->entries);
    }

    public function testNoNumberOrAddressInTheCacheKey(): void
    {
        $this->throttle->consume('otp_number', '+971501234567', 5, self::HOUR, self::NOON);
        $key = (string) array_key_first($this->cache->entries);
        self::assertStringNotContainsString('971501234567', $key);
        self::assertSame([Throttle::CACHE_TAG], $this->cache->tags[$key]);
    }

    public function testPeekNeverCountsAndHitAlwaysCounts(): void
    {
        for ($i = 0; $i < 10; $i++) {
            self::assertSame(0, $this->throttle->peek('otp_wrong_ip', '10.0.0.1', 3, self::HOUR, self::NOON));
        }
        self::assertSame([], $this->cache->entries);

        for ($i = 0; $i < 3; $i++) {
            $this->throttle->hit('otp_wrong_ip', '10.0.0.1', self::HOUR, self::NOON);
        }
        self::assertSame(
            self::HOUR - (self::NOON % self::HOUR),
            $this->throttle->peek('otp_wrong_ip', '10.0.0.1', 3, self::HOUR, self::NOON)
        );
        self::assertSame(0, $this->throttle->peek('otp_wrong_ip', '10.0.0.1', 4, self::HOUR, self::NOON));
        self::assertSame(0, $this->throttle->peek('otp_wrong_ip', '10.0.0.1', 3, self::HOUR, self::NOON + self::HOUR));
    }
}
