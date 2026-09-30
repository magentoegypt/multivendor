<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Store;

use MagentoEgypt\HubAppVendors\Model\Store\DispatchTime;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MagentoEgypt\HubAppVendors\Model\Store\DispatchTime
 */
class DispatchTimeTest extends TestCase
{
    public function testDeclaredCodes(): void
    {
        foreach (['same_day', 'next_day', 'days_2_3', 'days_3_5', 'days_5_7'] as $code) {
            $this->assertSame($code, DispatchTime::declaredCode($code));
        }
        $this->assertSame('days_2_3', DispatchTime::declaredCode(' days_2_3 '));
        $this->assertNull(DispatchTime::declaredCode(''));
        $this->assertNull(DispatchTime::declaredCode(null));
        $this->assertNull(DispatchTime::declaredCode('days_7_9'));
    }

    /**
     * @return array<string, array{int, float, int|null}>
     */
    public static function measuredCases(): array
    {
        return [
            'one shipment is not a figure' => [1, 10.0, null],
            'no time at all' => [5, 0.0, null],
            'negative (shipped before ordered)' => [5, -3.0, null],
            'within a day' => [2, 20.0, 1],
            'exactly a day' => [2, 24.0, 1],
            'just over a day' => [2, 24.5, 2],
            'three days' => [4, 60.0, 3],
            'exactly seven days' => [3, 168.0, 7],
            'beyond seven days' => [3, 168.5, null],
            'demo history, 69 days' => [9, 1656.0, null],
        ];
    }

    #[DataProvider('measuredCases')]
    public function testMeasuredDays(int $shipments, float $hours, ?int $expected): void
    {
        $this->assertSame($expected, DispatchTime::measuredDays($shipments, $hours));
    }

    public function testMeasuredCode(): void
    {
        $this->assertSame('measured_1', DispatchTime::measuredCode(1));
        $this->assertSame('measured_4', DispatchTime::measuredCode(4));
    }

    public function testEveryDeclaredCodeHasAShortLabel(): void
    {
        $this->assertSame(
            ['same_day', 'next_day', 'days_2_3', 'days_3_5', 'days_5_7'],
            array_keys(DispatchTime::DECLARED_LABELS)
        );
    }
}
