<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubApp\Test\Unit\Model\Product;

use MagentoEgypt\HubApp\Model\Product\PersonalPicks;
use PHPUnit\Framework\TestCase;

/**
 * The hmPickedForYou decision: personal only when personalization actually changed the picks.
 */
final class PersonalPicksTest extends TestCase
{
    private const FALLBACK = [9, 8, 7];

    public function testPersonalOrderWinsWhenPersonalizationMovedSomething(): void
    {
        $result = PersonalPicks::choose([3, 1, 2], [1, 2, 3], fn (): array => self::FALLBACK, 16);

        self::assertSame(['ids' => [3, 1, 2], 'personalized' => true], $result);
    }

    public function testSameOrderMeansNoProfileSoTheFallbackIsUsed(): void
    {
        $result = PersonalPicks::choose([1, 2, 3], [1, 2, 3], fn (): array => self::FALLBACK, 16);

        self::assertSame(['ids' => self::FALLBACK, 'personalized' => false], $result);
    }

    public function testOnlyTheFirstLimitPicksAreCompared(): void
    {
        //  A difference beyond the limit is not something the shopper would ever see.
        $result = PersonalPicks::choose([1, 2, 4], [1, 2, 3], fn (): array => self::FALLBACK, 2);

        self::assertSame(['ids' => self::FALLBACK, 'personalized' => false], $result);
    }

    public function testNoHitsFallBack(): void
    {
        $result = PersonalPicks::choose([], [], fn (): array => self::FALLBACK, 16);

        self::assertFalse($result['personalized']);
        self::assertSame(self::FALLBACK, $result['ids']);
    }

    public function testTokenShape(): void
    {
        self::assertTrue(PersonalPicks::isValidToken('anonymous-3f1c2a'));
        self::assertTrue(PersonalPicks::isValidToken('aG9tZQ=='));
        self::assertFalse(PersonalPicks::isValidToken(''));
        self::assertFalse(PersonalPicks::isValidToken('has space'));
        self::assertFalse(PersonalPicks::isValidToken(str_repeat('a', 130)));
    }
}
