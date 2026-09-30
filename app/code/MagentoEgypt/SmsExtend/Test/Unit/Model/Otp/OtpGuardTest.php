<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\SmsExtend\Test\Unit\Model\Otp;

use MagentoEgypt\SmsExtend\Model\Otp\OtpGuard;
use MagentoEgypt\SmsExtend\Model\Throttle;
use MagentoEgypt\SmsExtend\Test\Unit\ConfigStub;
use MagentoEgypt\SmsExtend\Test\Unit\MemoryCache;
use PHPUnit\Framework\TestCase;

/**
 * The WhatsApp codes' abuse limits, shared by the REST service (seller app) and the app's GraphQL.
 */
class OtpGuardTest extends TestCase
{
    /** 2026-09-29 12:30:00 UTC, 1800 s into its hour window. */
    private const NOON = 1790683200 + 1800;

    private MemoryCache $cache;

    private ConfigStub $config;

    protected function setUp(): void
    {
        $this->cache = new MemoryCache();
        $this->config = new ConfigStub();
    }

    private function guard(): OtpGuard
    {
        return new OtpGuard(new Throttle($this->cache), $this->cache, $this->config);
    }

    public function testWithNothingStoredTheNumberLimitIsFiveAndTheAddressLimitIsOff(): void
    {
        $guard = $this->guard();
        for ($i = 0; $i < 5; $i++) {
            self::assertSame(0, $guard->sendWait('+971501234567', '10.0.0.' . $i, self::NOON));
        }
        self::assertGreaterThan(0, $guard->sendWait('+971501234567', '10.0.0.9', self::NOON), 'sixth for the number');

        //  Off by default: behind a CDN every visitor can share one address.
        self::assertSame(0, OtpGuard::DEFAULT_SEND_LIMIT_IP);
        self::assertSame(0, OtpGuard::DEFAULT_WRONG_CODES_IP);
        for ($i = 0; $i < 30; $i++) {
            self::assertSame(0, $guard->sendWait(self::number($i), '10.0.1.1', self::NOON));
        }
    }

    public function testTheAddressLimitAppliesOnceSwitchedOn(): void
    {
        $this->config->values = [OtpGuard::XML_SEND_LIMIT_IP => '10'];
        $guard = $this->guard();
        for ($i = 0; $i < 10; $i++) {
            self::assertSame(0, $guard->sendWait('+97150000000' . $i, '10.0.1.1', self::NOON));
        }
        $eleventh = $guard->sendWait('+971509999999', '10.0.1.1', self::NOON);
        self::assertGreaterThan(0, $eleventh, 'eleventh for the address');
        self::assertSame(0, $guard->sendWait('+971509999999', '10.0.1.2', self::NOON), 'another address');
    }

    public function testEverySpellingOfANumberIsOneNumber(): void
    {
        $this->config->values = [OtpGuard::XML_SEND_LIMIT_NUMBER => '3'];
        $guard = $this->guard();

        self::assertSame(0, $guard->sendWait('0501234567', '10.0.0.1', self::NOON));
        self::assertSame(0, $guard->sendWait('+971 50 123 4567', '10.0.0.2', self::NOON));
        self::assertSame(0, $guard->sendWait('501234567', '10.0.0.3', self::NOON));
        self::assertSame(1800, $guard->sendWait('00971501234567', '10.0.0.4', self::NOON));
        self::assertSame(0, $guard->sendWait('+971501234568', '10.0.0.5', self::NOON), 'another number');
    }

    public function testZeroSwitchesALimitOff(): void
    {
        $this->config->values = [
            OtpGuard::XML_SEND_LIMIT_IP => '0',
            OtpGuard::XML_SEND_LIMIT_NUMBER => '0',
            OtpGuard::XML_WRONG_CODES_IP => '0',
        ];
        $guard = $this->guard();
        for ($i = 0; $i < 50; $i++) {
            self::assertSame(0, $guard->sendWait('+971501234567', '10.0.0.1', self::NOON));
            $guard->verifyFailed('+97150000' . str_pad((string) $i, 4, '0', STR_PAD_LEFT), '10.0.0.1', self::NOON);
            self::assertSame(0, $guard->verifyWait('+971509999999', '10.0.0.1', self::NOON));
        }
    }

    public function testFiveWrongCodesLockTheNumberForFifteenMinutes(): void
    {
        $guard = $this->guard();
        for ($i = 1; $i <= 4; $i++) {
            self::assertSame(0, $guard->verifyFailed('+971501234567', '10.0.0.1', self::NOON + $i));
            self::assertSame(0, $guard->verifyWait('+971501234567', '10.0.0.1', self::NOON + $i));
        }
        $fifth = $guard->verifyFailed('+971501234567', '10.0.0.2', self::NOON + 5);
        self::assertSame(OtpGuard::NUMBER_LOCK_SECONDS, $fifth);

        //  Locked for every spelling of the number and from every address; other numbers are not.
        self::assertSame(900, $guard->verifyWait('+971501234567', '10.0.0.3', self::NOON + 5));
        self::assertSame(600, $guard->verifyWait('0501234567', '10.0.0.4', self::NOON + 305));
        self::assertSame(0, $guard->verifyWait('+971501234568', '10.0.0.3', self::NOON + 305));
        //  Over after 15 minutes.
        self::assertSame(0, $guard->verifyWait('+971501234567', '10.0.0.3', self::NOON + 905));
    }

    public function testWrongCodesAreForgottenFifteenMinutesAfterTheLast(): void
    {
        $guard = $this->guard();
        for ($i = 1; $i <= 4; $i++) {
            $guard->verifyFailed('+971501234567', '10.0.0.1', self::NOON);
        }
        self::assertSame(0, $guard->verifyFailed('+971501234567', '10.0.0.1', self::NOON + 901));
        self::assertSame(0, $guard->verifyWait('+971501234567', '10.0.0.1', self::NOON + 901));
    }

    public function testARightCodeClearsTheWrongOnes(): void
    {
        $guard = $this->guard();
        for ($i = 1; $i <= 4; $i++) {
            $guard->verifyFailed('+971501234567', '10.0.0.1', self::NOON);
        }
        $guard->verifySucceeded('0501234567');
        for ($i = 1; $i <= 4; $i++) {
            self::assertSame(0, $guard->verifyFailed('+971501234567', '10.0.0.1', self::NOON + 1));
        }
        self::assertSame(0, $guard->verifyWait('+971501234567', '10.0.0.1', self::NOON + 1));
    }

    public function testEachAddressHasAnHourlyBudgetOfWrongCodes(): void
    {
        $this->config->values = [OtpGuard::XML_WRONG_CODES_IP => '3'];
        $guard = $this->guard();
        foreach (['+971501111111', '+971502222222', '+971503333333'] as $number) {
            self::assertSame(0, $guard->verifyWait($number, '10.0.0.1', self::NOON));
            $guard->verifyFailed($number, '10.0.0.1', self::NOON);
        }

        self::assertSame(1800, $guard->verifyWait('+971504444444', '10.0.0.1', self::NOON));
        self::assertSame(0, $guard->verifyWait('+971504444444', '10.0.0.2', self::NOON), 'another address');
        self::assertSame(0, $guard->verifyWait('+971504444444', '10.0.0.1', self::NOON + 1800), 'next hour');
    }

    public function testTheWrongCodeBudgetIsOffByDefaultAndCountsNothing(): void
    {
        $guard = $this->guard();
        for ($i = 0; $i < 40; $i++) {
            $guard->verifyFailed(self::number($i), '10.0.0.7', self::NOON);
        }

        self::assertSame(0, $guard->verifyWait('+971509999999', '10.0.0.7', self::NOON));
        //  One entry per number (its wrong codes), none for the address.
        self::assertCount(40, $this->cache->entries);
    }

    public function testTheWrongCodeBudgetAppliesOnceSwitchedOn(): void
    {
        $this->config->values = [OtpGuard::XML_WRONG_CODES_IP => '30'];
        $guard = $this->guard();
        for ($i = 0; $i < 30; $i++) {
            $guard->verifyFailed(self::number($i), '10.0.0.7', self::NOON);
        }

        self::assertGreaterThan(0, $guard->verifyWait('+971509999999', '10.0.0.7', self::NOON));
    }

    public function testNoNumberInAnyCacheKey(): void
    {
        //  The address limits are switched on so their counters exist too.
        $this->config->values = [OtpGuard::XML_SEND_LIMIT_IP => '10', OtpGuard::XML_WRONG_CODES_IP => '30'];
        $guard = $this->guard();
        $guard->sendWait('+971501234567', '10.0.0.1', self::NOON);
        $guard->verifyFailed('+971501234567', '10.0.0.1', self::NOON);
        foreach (array_keys($this->cache->entries) as $key) {
            self::assertStringNotContainsString('971501234567', $key);
            self::assertStringNotContainsString('10.0.0.1', $key);
            self::assertSame([Throttle::CACHE_TAG], $this->cache->tags[$key]);
        }
        self::assertCount(4, $this->cache->entries);
    }

    /**
     * A distinct UAE mobile number for each $i below 100.
     */
    private static function number(int $i): string
    {
        return '+9715010000' . str_pad((string) $i, 2, '0', STR_PAD_LEFT);
    }
}
