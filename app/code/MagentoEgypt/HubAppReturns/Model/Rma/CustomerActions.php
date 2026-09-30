<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

/**
 * What a customer may do with their return, by the rules of the website's return page
 * (Vnecoms\VendorsRMA\Block\Frontend\View and the request/view.phtml it renders). The website applies
 * them only when drawing the buttons; hmAddReturnMessage, hmCancelReturn and hmEscalateReturn enforce
 * them, and HmReturn.can_reply / can_cancel / can_escalate report them.
 *
 *  - reply: while the state is open, awaiting or being (View::isReplyRma shows the reply form);
 *  - cancel: while the status is pending or approval (View::isCancelRma shows Cancel);
 *  - escalate: unless the return is cancelled or has an escalation already (View::isEscalateRma:
 *    Request::canEscalate() is true only while ves_rma_request_escalate has no row for it) - a
 *    resolved return included, which the escalation re-opens for Hub Market;
 *  - escalating moves the status to "awaiting" (Escalated), or to "being" (Being Reviewed By Admin)
 *    when it is awaiting already (Controller\Customer\SaveEscalate).
 */
final class CustomerActions
{
    /** Statuses the website offers Cancel in (Vnecoms\RMA\Model\Request::STATUS_PENDING, STATUS_APPROVAL). */
    public const CANCELABLE_STATUSES = ['pending', 'approval'];

    /** Vnecoms\RMA\Model\Request::STATUS_CANCELED. */
    public const STATUS_CANCELED = 'canceled';

    /** Vnecoms\VendorsRMA\Model\Request::STATUS_AWAITING ("Escalated"). */
    public const STATUS_AWAITING = 'awaiting';

    /** Vnecoms\VendorsRMA\Model\Request::STATUS_BEING ("Being Reviewed By Admin"). */
    public const STATUS_BEING = 'being';

    /** ves_rma_request_entity.state values these rules read. */
    private const STATE_CANCELED = 'canceled';
    private const STATE_AWAITING = 'awaiting';

    private function __construct()
    {
    }

    public static function canReply(string $state): bool
    {
        return Vocabulary::acceptsReplies($state);
    }

    public static function canCancel(string $statusCode): bool
    {
        return in_array($statusCode, self::CANCELABLE_STATUSES, true);
    }

    /**
     * @param bool $escalated whether ves_rma_request_escalate has a row for the return
     */
    public static function canEscalate(string $state, bool $escalated): bool
    {
        return $state !== self::STATE_CANCELED && !$escalated;
    }

    /**
     * The status code an escalation moves the return to.
     */
    public static function escalationStatus(string $state): string
    {
        return $state === self::STATE_AWAITING ? self::STATUS_BEING : self::STATUS_AWAITING;
    }
}
