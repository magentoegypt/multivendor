<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Home;

use MagentoEgypt\HubApp\Model\Home\Schedule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Which Home rows are live: active, store, start/end (UTC), audience.
 */
final class ScheduleTest extends TestCase
{
    private const NOW = '2026-09-29 12:00:00';

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::NOW, new \DateTimeZone('UTC'));
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function row(array $row = []): array
    {
        return $row + ['is_active' => 1, 'store_id' => 0, 'audience' => 'all', 'starts_at' => null, 'ends_at' => null];
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: int, 2: string, 3: bool}>
     */
    public static function visibility(): array
    {
        return [
            'plain row' => [self::row(), 1, 'GUEST', true],
            'inactive' => [self::row(['is_active' => 0]), 1, 'GUEST', false],
            'all stores' => [self::row(['store_id' => 0]), 2, 'GUEST', true],
            'own store' => [self::row(['store_id' => 1]), 1, 'GUEST', true],
            'other store' => [self::row(['store_id' => 2]), 1, 'GUEST', false],
            'starts later' => [self::row(['starts_at' => '2026-09-29 12:00:01']), 1, 'GUEST', false],
            'starts now' => [self::row(['starts_at' => self::NOW]), 1, 'GUEST', true],
            'started' => [self::row(['starts_at' => '2026-09-01 00:00:00']), 1, 'GUEST', true],
            'ends now (gone)' => [self::row(['ends_at' => self::NOW]), 1, 'GUEST', false],
            'ends later' => [self::row(['ends_at' => '2026-09-29 12:00:01']), 1, 'GUEST', true],
            'zero date is empty' => [self::row(['ends_at' => '0000-00-00 00:00:00']), 1, 'GUEST', true],
            'guest row, guest' => [self::row(['audience' => 'guest']), 1, 'GUEST', true],
            'guest row, customer' => [self::row(['audience' => 'guest']), 1, 'CUSTOMER', false],
            'customer row, customer' => [self::row(['audience' => 'customer']), 1, 'CUSTOMER', true],
            'customer row, unknown asks as guest' => [self::row(['audience' => 'customer']), 1, 'nonsense', false],
            'all rows, customer' => [self::row(['audience' => 'all']), 1, 'CUSTOMER', true],
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    #[DataProvider('visibility')]
    public function testIsVisible(array $row, int $storeId, string $audience, bool $expected): void
    {
        self::assertSame($expected, Schedule::isVisible($row, $storeId, $audience, $this->now()));
    }

    public function testNormaliseAudience(): void
    {
        self::assertSame('CUSTOMER', Schedule::normaliseAudience(' customer '));
        self::assertSame('GUEST', Schedule::normaliseAudience(null));
        self::assertSame('GUEST', Schedule::normaliseAudience('anything'));
    }

    public function testNextBoundaryIsTheFirstFutureStartOrEnd(): void
    {
        $next = Schedule::nextBoundary([
            ['starts_at' => '2026-09-30 00:00:00', 'ends_at' => null],
            ['starts_at' => '2026-09-01 00:00:00', 'ends_at' => '2026-09-29 18:00:00'],
            ['starts_at' => null, 'ends_at' => '2026-09-29 11:00:00'],
        ], $this->now());

        self::assertNotNull($next);
        self::assertSame('2026-09-29 18:00:00', $next->format('Y-m-d H:i:s'));
    }

    public function testNoBoundaryWithoutFutureDates(): void
    {
        self::assertNull(Schedule::nextBoundary([['starts_at' => null, 'ends_at' => '2026-09-01 00:00:00']], $this->now()));
    }

    public function testIsoUtc(): void
    {
        self::assertSame('2026-10-01T21:00:00Z', Schedule::isoUtc('2026-10-01 21:00:00'));
        self::assertNull(Schedule::isoUtc(''));
        self::assertNull(Schedule::isoUtc(null));
        self::assertNull(Schedule::parseUtc('not a date'));
    }
}
