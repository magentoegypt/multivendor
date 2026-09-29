<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Model\Rma;

use MagentoEgypt\HubAppReturns\Model\Rma\Vocabulary;
use PHPUnit\Framework\TestCase;

class VocabularyTest extends TestCase
{
    public function testStates(): void
    {
        self::assertSame('OPEN', Vocabulary::state('open', 'pending'));
        self::assertSame('OPEN', Vocabulary::state('awaiting', ''));
        self::assertSame('OPEN', Vocabulary::state('being', ''));
        self::assertSame('CLOSED', Vocabulary::state('closed', 'resolved'));
        self::assertSame('CANCELED', Vocabulary::state('canceled', 'canceled'));
        //  A status without a ves_rma_status_state row saves state "" or "0".
        self::assertSame('CANCELED', Vocabulary::state('0', 'canceled'));
        self::assertSame('CLOSED', Vocabulary::state('', 'resolved'));
        self::assertSame('OPEN', Vocabulary::state('', 'package_sent'));
    }

    public function testReplies(): void
    {
        self::assertTrue(Vocabulary::acceptsReplies('open'));
        self::assertTrue(Vocabulary::acceptsReplies('awaiting'));
        self::assertTrue(Vocabulary::acceptsReplies('being'));
        self::assertFalse(Vocabulary::acceptsReplies('closed'));
        self::assertFalse(Vocabulary::acceptsReplies('canceled'));
    }

    public function testTypesAndActors(): void
    {
        self::assertSame('REFUND', Vocabulary::type('refund'));
        self::assertSame('REPLACE', Vocabulary::type('replace'));
        self::assertSame('CUSTOMER', Vocabulary::historyActor('customer'));
        self::assertSame('SELLER', Vocabulary::historyActor('vendor'));
        self::assertSame('HUB_MARKET', Vocabulary::historyActor('department'));
        self::assertSame('HUB_MARKET', Vocabulary::historyActor('admin'));
        self::assertSame('CUSTOMER', Vocabulary::messageActor('CUSTOMER REPLY'));
        self::assertSame('SELLER', Vocabulary::messageActor('VENDOR REPLY'));
        self::assertSame('HUB_MARKET', Vocabulary::messageActor('DEPARMENT REPLY'));
    }

    public function testUtc(): void
    {
        self::assertSame('2026-09-29T21:05:00Z', Vocabulary::utc('2026-09-29 21:05:00'));
        self::assertSame('', Vocabulary::utc(null));
        self::assertSame('', Vocabulary::utc('0000-00-00 00:00:00'));
        self::assertSame('', Vocabulary::utc('not a date'));
    }
}
