<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Test\Unit\Model\Otp;

use Magento\Framework\App\CacheInterface;
use MagentoEgypt\HubAppAccount\Model\Otp\SendThrottle;
use PHPUnit\Framework\TestCase;

class SendThrottleTest extends TestCase
{
    private const HOUR = 3600;

    /** 2026-09-29 12:30:00 UTC, 1800 s into its hour window. */
    private const NOON = 1790683200 + 1800;

    private MemoryCache $cache;

    private SendThrottle $throttle;

    protected function setUp(): void
    {
        $this->cache = new MemoryCache();
        $this->throttle = new SendThrottle($this->cache);
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
        self::assertSame([SendThrottle::CACHE_TAG], $this->cache->tags[$key]);
    }
}

/**
 * In-memory CacheInterface for the test.
 */
class MemoryCache implements CacheInterface
{
    /** @var array<string, string> */
    public array $entries = [];

    /** @var array<string, string[]> */
    public array $tags = [];

    public function getFrontend()
    {
        return null;
    }

    public function load($identifier)
    {
        return $this->entries[$identifier] ?? false;
    }

    public function save($data, $identifier, $tags = [], $lifeTime = null)
    {
        $this->entries[$identifier] = (string) $data;
        $this->tags[$identifier] = $tags;

        return true;
    }

    public function remove($identifier)
    {
        unset($this->entries[$identifier], $this->tags[$identifier]);

        return true;
    }

    public function clean($tags = [])
    {
        $this->entries = [];
        $this->tags = [];

        return true;
    }
}
