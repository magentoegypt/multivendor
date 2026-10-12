<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\FulfillmentTracking\Model;

/**
 * The customer's delivery vocabulary, and how every internal state maps onto it. Pure: no I/O.
 *
 * MagentoEgypt_Fulfillment records staff-facing states (Dispatch: unclassified, planned, ready,
 * dispatched, shipped_reported, received, received_reported, delivered, delivered_reported,
 * issue; Workflow: planned, dispatched, received, delivered). A customer is told where their
 * parcel is, not who reported it, so `shipped_reported` and `dispatched` read the same, and an
 * internal issue note is never shown, only that the delivery is delayed.
 *
 * A group nobody has recorded progress for ("untracked": every order placed before the module,
 * and any order its team has not touched yet) is not guessed at. It follows the order the website
 * shows: the vendor order's state, else the order's, and Magento shipments of its lines.
 */
final class StatusMap
{
    public const ORDERED = 'ordered';
    public const PREPARING = 'preparing';
    public const READY = 'ready_for_pickup';
    public const TO_HUB = 'to_hub';
    public const AT_HUB = 'at_hub';
    public const SHIPPED = 'shipped';
    public const ON_THE_WAY = 'on_the_way';
    public const DELIVERED = 'delivered';
    public const COMPLETED = 'completed';
    public const DELAYED = 'delayed';
    public const ON_HOLD = 'on_hold';
    public const CANCELED = 'canceled';
    public const REFUNDED = 'refunded';

    /** Fulfillment leg => customer route code. */
    public const ROUTES = ['direct' => 'direct', 'inbound' => 'to_hub', 'outbound' => 'from_hub'];

    /** How far along a status is. Statuses outside the line (delayed, on hold, ended) have none. */
    private const RANK = [
        self::ORDERED => 0,
        self::PREPARING => 1,
        self::READY => 2,
        self::TO_HUB => 3,
        self::AT_HUB => 4,
        self::SHIPPED => 5,
        self::ON_THE_WAY => 5,
        self::DELIVERED => 7,
        self::COMPLETED => 7,
    ];

    private const ENDED = [self::CANCELED, self::REFUNDED];

    public function route(string $leg): string
    {
        return self::ROUTES[$leg] ?? 'direct';
    }

    /**
     * An order state that decides every delivery on its own, or null when the deliveries decide.
     */
    public function forOrderState(string $orderState): ?string
    {
        return match ($orderState) {
            'canceled' => self::CANCELED,
            'closed' => self::REFUNDED,
            'holded' => self::ON_HOLD,
            'new', 'pending_payment', 'payment_review' => self::ORDERED,
            default => null,
        };
    }

    /**
     * A recorded fulfillment state (Dispatch or Workflow) as the customer reads it; null when the
     * state records nothing yet (unclassified, or a Workflow group still planned).
     */
    public function forRecorded(string $state, string $leg): ?string
    {
        return match ($state) {
            'planned' => self::PREPARING,
            'ready' => self::READY,
            'dispatched', 'shipped_reported' => $leg === 'inbound' ? self::TO_HUB : self::ON_THE_WAY,
            'received', 'received_reported' => self::AT_HUB,
            'delivered', 'delivered_reported' => self::DELIVERED,
            'issue' => self::DELAYED,
            default => null,
        };
    }

    /**
     * Status of one delivery group.
     *
     * @param string $leg direct|inbound|outbound
     * @param string $dispatchState Dispatch snapshot state
     * @param int $dispatchVersion 0 while nobody has recorded anything through Dispatch
     * @param string $sourceState Workflow state, or legacy_unallocated
     * @param string $untracked what the order itself says, see untracked()
     * @return array{0: string, 1: bool} [status, tracked]
     */
    public function forGroup(
        string $leg,
        string $dispatchState,
        int $dispatchVersion,
        string $sourceState,
        string $untracked
    ): array {
        $fromDispatch = $dispatchVersion > 0 ? $this->forRecorded($dispatchState, $leg) : null;
        if ($fromDispatch === self::DELAYED) {
            return [self::DELAYED, true];
        }
        $fromSource = in_array($sourceState, ['planned', 'legacy_unallocated', ''], true)
            ? null
            : $this->forRecorded($sourceState, $leg);

        $best = $this->furthest(array_filter([$fromDispatch, $fromSource]));
        if ($best !== null) {
            //  A group the team still has at "preparing" while Magento already shipped its lines is
            //  not held back: the customer sees what the order page shows.
            if ($best === self::PREPARING && in_array($untracked, [self::SHIPPED, self::COMPLETED], true)) {
                return [$untracked, false];
            }
            return [$best, true];
        }

        return [$untracked, false];
    }

    /**
     * What the order says for a group nobody has recorded progress for.
     *
     * @param string $orderState Magento order state
     * @param string|null $vendorState state of the Vnecoms vendor order for the group's seller, if any
     * @param bool $allShipped every line of the group shipped in Magento
     */
    public function untracked(string $orderState, ?string $vendorState, bool $allShipped): string
    {
        $state = $vendorState ?? $orderState;
        return match (true) {
            $state === 'canceled' => self::CANCELED,
            $state === 'closed' => self::REFUNDED,
            $state === 'holded' => self::ON_HOLD,
            $state === 'complete' => self::COMPLETED,
            $allShipped => self::SHIPPED,
            default => self::PREPARING,
        };
    }

    /**
     * The order's overall status from its deliveries.
     *
     * @param array<int, array{route: string, status_code: string}> $deliveries
     */
    public function overall(string $orderState, array $deliveries): string
    {
        $forced = $this->forOrderState($orderState);
        if ($forced !== null) {
            return $forced;
        }

        $active = array_values(array_filter(
            $deliveries,
            static fn (array $d): bool => !in_array($d['status_code'], self::ENDED, true)
        ));
        if (!$active) {
            //  Every delivery ended: all refunded reads refunded, anything else canceled.
            $codes = array_unique(array_column($deliveries, 'status_code'));
            return $codes === [self::REFUNDED] ? self::REFUNDED : ($codes ? self::CANCELED : self::PREPARING);
        }

        $codes = array_column($active, 'status_code');
        foreach ([self::DELAYED, self::ON_HOLD] as $blocking) {
            if (in_array($blocking, $codes, true)) {
                return $blocking;
            }
        }

        //  Through the warehouse: the last leg speaks once it moves; until then the legs into the
        //  warehouse do, the slowest one first.
        $outbound = array_values(array_filter($active, static fn (array $d): bool => $d['route'] === 'from_hub'));
        if ($outbound) {
            $last = $outbound[0]['status_code'];
            if (($this->rank($last) ?? 0) >= self::RANK[self::SHIPPED]) {
                return $last;
            }
            $inbound = array_column(
                array_filter($active, static fn (array $d): bool => $d['route'] === 'to_hub'),
                'status_code'
            );
            return $inbound ? $this->slowest($inbound) : $last;
        }

        return $this->slowest($codes);
    }

    public function rank(string $status): ?int
    {
        return self::RANK[$status] ?? null;
    }

    /**
     * @param string[] $statuses
     */
    private function furthest(array $statuses): ?string
    {
        $best = null;
        foreach ($statuses as $status) {
            if ($best === null || ($this->rank($status) ?? -1) > ($this->rank($best) ?? -1)) {
                $best = $status;
            }
        }
        return $best;
    }

    /**
     * @param string[] $statuses
     */
    private function slowest(array $statuses): string
    {
        $slowest = null;
        foreach ($statuses as $status) {
            if ($slowest === null || ($this->rank($status) ?? 99) < ($this->rank($slowest) ?? 99)) {
                $slowest = $status;
            }
        }
        return $slowest ?? self::PREPARING;
    }
}
