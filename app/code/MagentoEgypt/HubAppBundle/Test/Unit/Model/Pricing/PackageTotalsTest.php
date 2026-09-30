<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppBundle\Test\Unit\Model\Pricing;

use MagentoEgypt\HubAppBundle\Model\Pricing\PackageTotals;
use PHPUnit\Framework\TestCase;

/**
 * @covers \MagentoEgypt\HubAppBundle\Model\Pricing\PackageTotals
 */
class PackageTotalsTest extends TestCase
{
    public function testTheFigma14bPackage(): void
    {
        self::assertSame(
            ['price' => 61.0, 'row_total' => 61.0, 'regular_total' => 72.0, 'saving' => 11.0, 'discount_percent' => 15],
            PackageTotals::of(61.0, 1.0, 72.0)
        );
    }

    public function testTheRowTotalIsTheRoundedUnitTimesTheQuantityAsTheCartLine(): void
    {
        //  calcRowTotal(): round(unit) * qty, never round(unit * qty) of an unrounded unit.
        $totals = PackageTotals::of(33.335, 3.0, null);
        self::assertSame(33.34, $totals['price']);
        self::assertSame(100.02, $totals['row_total']);
    }

    public function testNoSavingWithoutARealDifference(): void
    {
        foreach ([
            'no regular price' => [50.0, null],
            'same price' => [50.0, 50.0],
            'half a cent' => [50.0, 50.004],
            'dearer than separately' => [55.0, 50.0],
            'zero regular' => [0.0, 0.0],
        ] as $case => [$unit, $regular]) {
            $totals = PackageTotals::of($unit, 2.0, $regular);
            self::assertNull($totals['regular_total'], $case);
            self::assertNull($totals['saving'], $case);
            self::assertSame(0, $totals['discount_percent'], $case);
        }
    }

    public function testARealSavingOfACent(): void
    {
        $totals = PackageTotals::of(9.99, 1.0, 10.0);
        self::assertSame(10.0, $totals['regular_total']);
        self::assertSame(0.01, $totals['saving']);
        self::assertSame(0, $totals['discount_percent'], '0.1% rounds to 0');
    }

    public function testANegativeUnitPriceIsNeverQuoted(): void
    {
        self::assertSame(0.0, PackageTotals::of(-5.0, 1.0, null)['price']);
    }
}
