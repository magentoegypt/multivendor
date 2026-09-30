<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Test\Unit\Model\Rma;

use MagentoEgypt\HubAppReturns\Model\Rma\CustomerActions;
use PHPUnit\Framework\TestCase;

/**
 * What the website's return page offers the customer (Vnecoms\VendorsRMA\Block\Frontend\View):
 * the reply form, Cancel, Escalate RMA, and where an escalation takes the return.
 */
final class CustomerActionsTest extends TestCase
{
    public function testRepliesWhileOpenAwaitingOrBeingReviewed(): void
    {
        foreach (['open', 'awaiting', 'being'] as $state) {
            self::assertTrue(CustomerActions::canReply($state), $state);
        }
        foreach (['closed', 'canceled', ''] as $state) {
            self::assertFalse(CustomerActions::canReply($state), $state);
        }
    }

    public function testCancelWhilePendingOrAccepted(): void
    {
        self::assertTrue(CustomerActions::canCancel('pending'));
        self::assertTrue(CustomerActions::canCancel('approval'));
        foreach (['package_sent', 'package_received', 'package_returned', 'resolved', 'canceled', 'awaiting', 'being', ''] as $code) {
            self::assertFalse(CustomerActions::canCancel($code), $code);
        }
    }

    public function testEscalateOnceUnlessCancelled(): void
    {
        self::assertTrue(CustomerActions::canEscalate('open', false));
        //  A resolved return too: the escalation re-opens it for Hub Market, as on the website.
        self::assertTrue(CustomerActions::canEscalate('closed', false));
        self::assertFalse(CustomerActions::canEscalate('canceled', false));
        self::assertFalse(CustomerActions::canEscalate('open', true));
        self::assertFalse(CustomerActions::canEscalate('awaiting', true));
    }

    public function testAnEscalationGoesToEscalatedOrOnToBeingReviewed(): void
    {
        self::assertSame('awaiting', CustomerActions::escalationStatus('open'));
        self::assertSame('awaiting', CustomerActions::escalationStatus('closed'));
        self::assertSame('being', CustomerActions::escalationStatus('awaiting'));
    }
}
