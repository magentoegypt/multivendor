<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppVendors\Test\Unit\Model\Offer;

use MagentoEgypt\HubAppVendors\Model\Offer\FamilyReader;
use PHPUnit\Framework\TestCase;

/**
 * Select-and-sell families from "copy => main product" pairs (the live data of
 * 26 Sep 2026: main 1 with copies 2150 and 2287, main 2244 with copy 2146).
 */
final class FamilyReaderTest extends TestCase
{
    private const PAIRS = [2150 => 1, 2287 => 1, 2146 => 2244];

    public function testTheMainProductsFamilyIsItselfAndEveryCopy(): void
    {
        self::assertSame([1 => [1, 2150, 2287]], FamilyReader::group([1], self::PAIRS));
    }

    public function testACopysFamilyIsItsMainProductAndTheOtherCopies(): void
    {
        self::assertSame(
            [2150 => [1, 2150, 2287], 2146 => [2146, 2244]],
            FamilyReader::group([2150, 2146], self::PAIRS)
        );
    }

    public function testAProductNobodyCopiedIsAFamilyOfOne(): void
    {
        self::assertSame([7 => [7]], FamilyReader::group([7], self::PAIRS));
        self::assertSame([7 => [7]], FamilyReader::group([7], []));
    }

    public function testIdsAreNormalisedAndBrokenPairsIgnored(): void
    {
        $families = FamilyReader::group(['1', 1, 0, -3, 2244], self::PAIRS + [5 => 5, 6 => 0]);

        self::assertSame([1, 2244], array_keys($families));
        self::assertSame([2146, 2244], $families[2244]);
        self::assertSame([1, 2, 3], FamilyReader::normalise([1, '2', 2, 0, 3, null]));
    }
}
