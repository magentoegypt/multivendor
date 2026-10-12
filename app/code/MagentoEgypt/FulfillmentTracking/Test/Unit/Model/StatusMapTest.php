<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\FulfillmentTracking\Test\Unit\Model;

use MagentoEgypt\FulfillmentTracking\Model\StatusMap as S;
use PHPUnit\Framework\TestCase;

final class StatusMapTest extends TestCase
{
    private S $map;

    protected function setUp(): void
    {
        $this->map = new S();
    }

    /**
     * Reported and confirmed moves read the same to a customer; the leg decides "to you" or "to the warehouse".
     */
    public function testRecordedStatesUseTheCustomerVocabulary(): void
    {
        self::assertSame(S::ON_THE_WAY, $this->map->forRecorded('shipped_reported', 'direct'));
        self::assertSame(S::ON_THE_WAY, $this->map->forRecorded('dispatched', 'outbound'));
        self::assertSame(S::TO_HUB, $this->map->forRecorded('dispatched', 'inbound'));
        self::assertSame(S::AT_HUB, $this->map->forRecorded('received_reported', 'inbound'));
        self::assertSame(S::DELIVERED, $this->map->forRecorded('delivered_reported', 'direct'));
        self::assertSame(S::DELAYED, $this->map->forRecorded('issue', 'direct'));
        self::assertNull($this->map->forRecorded('unclassified', 'direct'));
    }

    public function testAGroupNobodyRecordedFollowsTheOrder(): void
    {
        self::assertSame([S::PREPARING, false], $this->map->forGroup('direct', 'unclassified', 0, 'legacy_unallocated', S::PREPARING));
        self::assertSame([S::SHIPPED, false], $this->map->forGroup('direct', 'unclassified', 0, 'legacy_unallocated', S::SHIPPED));
    }

    public function testRecordedProgressWinsAndAnIssueShowsAsDelayed(): void
    {
        self::assertSame([S::ON_THE_WAY, true], $this->map->forGroup('direct', 'shipped_reported', 3, 'legacy_unallocated', S::PREPARING));
        self::assertSame([S::DELAYED, true], $this->map->forGroup('direct', 'issue', 4, 'dispatched', S::SHIPPED));
        //  Warehouse workflow ahead of the dispatch report: the further one shows.
        self::assertSame([S::DELIVERED, true], $this->map->forGroup('outbound', 'dispatched', 2, 'delivered', S::SHIPPED));
    }

    public function testClassifiedButUnmovedDoesNotHideAMagentoShipment(): void
    {
        self::assertSame([S::SHIPPED, false], $this->map->forGroup('direct', 'planned', 1, 'legacy_unallocated', S::SHIPPED));
    }

    public function testUntrackedUsesTheVendorOrderBeforeTheOrder(): void
    {
        self::assertSame(S::CANCELED, $this->map->untracked('processing', 'canceled', false));
        self::assertSame(S::COMPLETED, $this->map->untracked('processing', 'complete', true));
        self::assertSame(S::COMPLETED, $this->map->untracked('complete', null, true));
        self::assertSame(S::SHIPPED, $this->map->untracked('processing', 'processing', true));
        self::assertSame(S::PREPARING, $this->map->untracked('processing', null, false));
    }

    public function testOrderStatesThatDecideEverything(): void
    {
        self::assertSame(S::CANCELED, $this->map->overall('canceled', [['route' => 'direct', 'status_code' => S::DELIVERED]]));
        self::assertSame(S::REFUNDED, $this->map->overall('closed', []));
        self::assertSame(S::ORDERED, $this->map->overall('pending_payment', [['route' => 'direct', 'status_code' => S::PREPARING]]));
    }

    public function testDirectDeliveriesReportTheSlowestAndIgnoreCanceledParts(): void
    {
        self::assertSame(S::PREPARING, $this->map->overall('processing', [
            ['route' => 'direct', 'status_code' => S::DELIVERED],
            ['route' => 'direct', 'status_code' => S::PREPARING],
        ]));
        self::assertSame(S::DELIVERED, $this->map->overall('processing', [
            ['route' => 'direct', 'status_code' => S::DELIVERED],
            ['route' => 'direct', 'status_code' => S::CANCELED],
        ]));
        self::assertSame(S::DELAYED, $this->map->overall('processing', [
            ['route' => 'direct', 'status_code' => S::ON_THE_WAY],
            ['route' => 'direct', 'status_code' => S::DELAYED],
        ]));
    }

    public function testThroughTheWarehouseTheLastLegSpeaksOnceItMoves(): void
    {
        $inbound = [
            ['route' => 'to_hub', 'status_code' => S::AT_HUB],
            ['route' => 'to_hub', 'status_code' => S::TO_HUB],
        ];
        self::assertSame(S::TO_HUB, $this->map->overall('processing', [...$inbound, ['route' => 'from_hub', 'status_code' => S::PREPARING]]));
        self::assertSame(S::ON_THE_WAY, $this->map->overall('processing', [...$inbound, ['route' => 'from_hub', 'status_code' => S::ON_THE_WAY]]));
    }
}
