<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

/**
 * How many units of an order line can still be returned: the website's formula.
 *
 * Vnecoms computes it in two places that agree, the new-return item list
 * (Vnecoms\RMA\Block\Frontend\Customer\NewRequest\DefaultItems::getRmaItem) and the save-time check
 * (Vnecoms\RMA\Model\Request::validateItems):
 *
 *   order STATUS "complete":  (shipped == invoiced ? shipped : invoiced) - refunded - in returns
 *   any other status:         invoiced - in returns                       (refunds not subtracted)
 *
 * "In returns" is the line's quantity in non-cancelled requests (Vnecoms\RMA\Model\Item::
 * getAllRmaByItemId). The result is floored at 0 and cut to a whole number, as validateItems() does
 * with (int): ves_rma_request_item.qty is an integer column.
 *
 * Kept identical to the website on purpose, quirks included (the order status rather than its state,
 * refunds ignored outside "complete"): Request::validateItems() re-checks every return the app files
 * with this same formula, so any difference would surface as the website's "Wrong Qty Item.".
 */
final class ReturnableQty
{
    public const STATUS_COMPLETE = 'complete';

    public function calculate(
        string $orderStatus,
        float $qtyShipped,
        float $qtyInvoiced,
        float $qtyRefunded,
        float $qtyInReturns
    ): int {
        if ($orderStatus === self::STATUS_COMPLETE) {
            $base = $qtyShipped == $qtyInvoiced ? $qtyShipped : $qtyInvoiced;
            $qty = $base - $qtyRefunded - $qtyInReturns;
        } else {
            $qty = $qtyInvoiced - $qtyInReturns;
        }

        return $qty > 0 ? (int) $qty : 0;
    }
}
