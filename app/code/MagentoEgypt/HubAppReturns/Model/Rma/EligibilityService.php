<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Sales\Model\Order;
use Vnecoms\RMA\Helper\Config as RmaConfig;

/**
 * What a signed-in customer may return, by the website's rules (P2 design 9.3):
 *
 *  - the customer's own orders in state processing or complete (Vnecoms\RMA\Controller\Customer\
 *    Ajaxproduct, which loads the item list only for those states);
 *  - the lines the website's form offers (ReturnableLines): top-level lines, except that a core
 *    bundle is returned through its child lines, never through the bundle line itself;
 *  - how many units: ReturnableQty, the website's formula, on the line picked (for a bundle's child,
 *    the child line's own shipped, invoiced and refunded quantities, as bundle.phtml reads them);
 *  - partial quantities when rma/general/allow_per_order is on (it is by default), otherwise the whole
 *    remaining quantity;
 *  - one seller per return (Vnecoms\VendorsRMA\Observer\RequestValidateItem; Hub Market's own lines
 *    count as one seller);
 *  - a custom refund of at most the lines' (row total incl. tax - discount) / qty ordered x qty;
 *  - no return window for customers (rma/general/order_expiry_day applies to guests only).
 *
 * Unlike the website, which loads the order from the posted number and never checks it
 * (Vnecoms\VendorsRMA\Controller\Customer\Save, Request::validateItems), the order must be the
 * signed-in customer's and every line must belong to it.
 */
class EligibilityService
{
    /** Order states returns open for. */
    public const ELIGIBLE_STATES = [Order::STATE_PROCESSING, Order::STATE_COMPLETE];

    /** Newest orders scanned for hmReturnableOrders: a safety cap, since customers have no window. */
    public const MAX_ORDERS_SCANNED = 1000;

    /** sales_order columns an HmReturnableOrder is built from. */
    private const ORDER_COLUMNS = ['entity_id', 'increment_id', 'created_at', 'status', 'state', 'order_currency_code'];

    public const MAX_LINES_PER_RETURN = 100;
    public const MAX_COMMENT_LENGTH = 5000;
    public const MAX_OTHER_REASON_LENGTH = 255;
    public const MAX_TRACKING_LENGTH = 255;

    /** ves_rma_request_entity.state of a cancelled request (Vnecoms\RMA\Model\Request::STATE_CANCELED). */
    private const STATE_CANCELED = 'canceled';

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly ReturnableQty $returnableQty,
        private readonly OrderLineReader $lines,
        private readonly LabelReader $labels,
        private readonly RmaConfig $rmaConfig
    ) {
    }

    /**
     * The customer's orders with at least one returnable line, newest first, one page.
     *
     * @return array{total: int, items: array<int, array<string, mixed>>} HmReturnableOrder rows
     */
    public function returnableOrders(int $customerId, int $storeId, Paging $paging): array
    {
        $connection = $this->resource->getConnection();
        $orders = $connection->fetchAll(
            $connection->select()
                ->from($this->resource->getTableName('sales_order'), self::ORDER_COLUMNS)
                ->where('customer_id = ?', $customerId)
                ->where('state IN (?)', self::ELIGIBLE_STATES)
                ->order(['created_at DESC', 'entity_id DESC'])
                ->limit(self::MAX_ORDERS_SCANNED)
        );
        if (!$orders) {
            return ['total' => 0, 'items' => []];
        }

        $ordersById = [];
        foreach ($orders as $order) {
            $ordersById[(int) $order['entity_id']] = $order;
        }
        $candidates = $this->candidates($ordersById);

        $eligible = [];
        foreach ($ordersById as $orderId => $order) {
            if (self::hasReturnableLine($candidates, $orderId)) {
                $eligible[] = $order;
            }
        }

        $page = array_slice($eligible, $paging->offset(), $paging->pageSize);

        return [
            'total' => count($eligible),
            'items' => $page ? $this->orderRows($page, $candidates, $storeId) : [],
        ];
    }

    /**
     * The lines a customer can pick in these orders (ReturnableLines) and how many units of each can be
     * returned now (ReturnableQty, less what non-cancelled returns hold).
     *
     * @param array<int, array<string, mixed>> $ordersById sales_order rows by entity id
     * @return array{lines: array<int, array<string, mixed>>, byOrder: array<int, array<int, array<string, mixed>>>,
     *     returnable: array<int, int>, held: array<int, array{qty: float, numbers: string[]}>}
     */
    private function candidates(array $ordersById): array
    {
        $lines = $this->lines->linesOfOrders(array_keys($ordersById));
        $offered = ReturnableLines::offered($lines);
        $held = $this->heldInReturns(array_keys($offered));

        $returnable = [];
        $byOrder = [];
        foreach ($offered as $itemId => $line) {
            $orderId = (int) $line['order_id'];
            $returnable[$itemId] = $this->returnableQty->calculate(
                (string) $ordersById[$orderId]['status'],
                (float) $line['qty_shipped'],
                (float) $line['qty_invoiced'],
                (float) $line['qty_refunded'],
                $held[$itemId]['qty'] ?? 0.0
            );
            $byOrder[$orderId][$itemId] = $line;
        }

        return ['lines' => $lines, 'byOrder' => $byOrder, 'returnable' => $returnable, 'held' => $held];
    }

    /**
     * @param array{byOrder: array<int, array<int, array<string, mixed>>>, returnable: array<int, int>} $candidates
     */
    private static function hasReturnableLine(array $candidates, int $orderId): bool
    {
        foreach (array_keys($candidates['byOrder'][$orderId] ?? []) as $itemId) {
            if ($candidates['returnable'][$itemId] >= 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * HmReturnableOrder rows for these orders, in the order given: every line the website's form offers
     * with what is returnable of it and what it was paid, in the order currency. The prices are the
     * refund cap's own figures (OrderLineReader::refundPerUnit, the formula prepare() checks a custom
     * refund with and Vnecoms stores as the full amount), a bundle's child line with its own.
     *
     * @param array<int, array<string, mixed>> $orders sales_order rows (ORDER_COLUMNS)
     * @param array<string, mixed> $candidates candidates() of these orders
     * @return array<int, array<string, mixed>>
     */
    private function orderRows(array $orders, array $candidates, int $storeId): array
    {
        $pageLines = [];
        foreach ($orders as $order) {
            $pageLines += $candidates['byOrder'][(int) $order['entity_id']] ?? [];
        }
        $shown = $this->lines->present($pageLines, $storeId);
        $statusLabels = $this->labels->orderStatusLabels(array_column($orders, 'status'), $storeId);

        $items = [];
        foreach ($orders as $order) {
            $orderId = (int) $order['entity_id'];
            $currency = (string) ($order['order_currency_code'] ?? '');
            $rows = [];
            foreach ($candidates['byOrder'][$orderId] ?? [] as $itemId => $line) {
                $options = $shown[$itemId]['options'] ?? [];
                $bundle = ReturnableLines::bundleOf($candidates['lines'], $itemId);
                $bundleName = trim((string) ($bundle['name'] ?? ''));
                if ($bundleName !== '') {
                    //  The website lists a bundle's items under the bundle's name; the app's list is flat.
                    array_unshift($options, ['label' => (string) __('Part of bundle'), 'value' => $bundleName]);
                }
                $returnable = $candidates['returnable'][$itemId];
                $unit = $this->lines->refundPerUnit($line);
                $rows[] = [
                    'order_item_id' => $itemId,
                    'sku' => $shown[$itemId]['sku'] ?? (string) $line['sku'],
                    'name' => $shown[$itemId]['name'] ?? (string) $line['name'],
                    'image_url' => $shown[$itemId]['image_url'] ?? null,
                    'options' => $options,
                    'qty_ordered' => (float) $line['qty_ordered'],
                    'qty_returnable' => (float) $returnable,
                    'open_return_numbers' => $candidates['held'][$itemId]['numbers'] ?? [],
                    'seller' => $shown[$itemId]['seller'] ?? null,
                    'unit_price' => Vocabulary::money($unit, $currency),
                    'row_total' => Vocabulary::money($this->lines->refundBase($line), $currency),
                    'max_refund' => Vocabulary::money($unit * $returnable, $currency),
                ];
            }
            $items[] = [
                'order_number' => (string) $order['increment_id'],
                'created_at' => Vocabulary::utc((string) $order['created_at']),
                'status_label' => $statusLabels[(string) $order['status']] ?? (string) $order['status'],
                'items' => $rows,
            ];
        }

        return $items;
    }

    /**
     * Quantity of each line in non-cancelled returns, and those returns' numbers (oldest first).
     *
     * @param int[] $orderItemIds
     * @return array<int, array{qty: float, numbers: string[]}>
     */
    public function heldInReturns(array $orderItemIds): array
    {
        $orderItemIds = array_values(array_filter(array_map('intval', $orderItemIds)));
        if (!$orderItemIds) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $rows = $connection->fetchAll(
            $connection->select()
                ->from(['i' => $this->resource->getTableName('ves_rma_request_item')], ['order_item_id', 'qty'])
                ->join(
                    ['r' => $this->resource->getTableName('ves_rma_request_entity')],
                    'r.entity_id = i.request_id',
                    ['increment_id', 'state']
                )
                ->where('i.order_item_id IN (?)', $orderItemIds)
                ->order(['r.entity_id ASC', 'i.item_id ASC'])
        );

        $out = [];
        foreach ($rows as $row) {
            if ((string) $row['state'] === self::STATE_CANCELED) {
                continue;
            }
            $itemId = (int) $row['order_item_id'];
            $out[$itemId] ??= ['qty' => 0.0, 'numbers' => []];
            $out[$itemId]['qty'] += (float) $row['qty'];
            $number = (string) $row['increment_id'];
            if ($number !== '' && !in_array($number, $out[$itemId]['numbers'], true)) {
                $out[$itemId]['numbers'][] = $number;
            }
        }

        return $out;
    }

    /**
     * Check an hmCreateReturn input against the rules and return what the request is made of.
     *
     * @param array<string, mixed> $input HmCreateReturnInput
     * @return array{order: array<string, mixed>, items: array<int, array{item_id: int, item_qty: int}>,
     *     type: string, reason: int, other_reason: string, package_opened: int, comment: string,
     *     refund_amount_type: string, refund_custom_amount: ?float, tracking_code: string}
     * @throws GraphQlInputException|GraphQlNoSuchEntityException
     */
    public function prepare(int $customerId, int $storeId, array $input): array
    {
        $number = trim(ltrim(trim((string) ($input['order_number'] ?? '')), '#'));
        if ($number === '') {
            throw new GraphQlInputException(__('Enter the order number.'));
        }
        $order = $this->customerOrder($customerId, $number);
        if ($order === null) {
            //  Same answer for "no such order" and "someone else's order".
            throw new GraphQlNoSuchEntityException(__('We couldn\'t find order %1 in your account.', $number));
        }
        if (!in_array((string) $order['state'], self::ELIGIBLE_STATES, true)) {
            throw new GraphQlInputException(
                __('Order %1 can\'t be returned yet: returns open once the order is processing or complete.', $number)
            );
        }

        $requested = $this->requestedQuantities($input['items'] ?? null);
        $lines = $this->lines->linesOfOrders([(int) $order['entity_id']]);
        foreach (array_keys($requested) as $itemId) {
            switch (ReturnableLines::classify($lines, $itemId)) {
                case ReturnableLines::OFFERED:
                    break;
                case ReturnableLines::BUNDLE:
                    //  Filed on the bundle line, the return would list no items in the admin and seller panels.
                    throw new GraphQlInputException(__(
                        '"%1" is a bundle: choose the items inside it that you want to return.',
                        (string) $lines[$itemId]['name']
                    ));
                case ReturnableLines::PART:
                    throw new GraphQlInputException(
                        __('Item %1 can\'t be returned on its own: choose the line it belongs to.', $itemId)
                    );
                default:
                    throw new GraphQlInputException(__('Item %1 is not a line of order %2.', $itemId, $number));
            }
        }
        $held = $this->heldInReturns(array_keys($requested));
        $partialAllowed = (bool) $this->rmaConfig->allowPerOrder();

        $items = [];
        $sellers = [];
        $refundCap = 0.0;
        foreach ($requested as $itemId => $qty) {
            $line = $lines[$itemId];
            $name = (string) $line['name'];
            if ($qty < 1 || floor($qty) !== $qty) {
                throw new GraphQlInputException(__('Enter a whole quantity of at least 1 for "%1".', $name));
            }
            $returnable = $this->returnableQty->calculate(
                (string) $order['status'],
                (float) $line['qty_shipped'],
                (float) $line['qty_invoiced'],
                (float) $line['qty_refunded'],
                $held[$itemId]['qty'] ?? 0.0
            );
            if ($returnable < 1) {
                throw new GraphQlInputException(
                    __('"%1" can\'t be returned: it isn\'t invoiced yet, or it is already in a return.', $name)
                );
            }
            if ($qty > $returnable) {
                throw new GraphQlInputException(__('You can return at most %1 of "%2".', $returnable, $name));
            }
            if (!$partialAllowed && (int) $qty !== $returnable) {
                throw new GraphQlInputException(
                    __('Return the whole remaining quantity (%1) of "%2".', $returnable, $name)
                );
            }
            //  RequestValidateItem groups by the line's vendor_id, Hub Market's lines (0) together.
            $sellers[(int) $line['vendor_id']] = true;
            $refundCap += $this->lines->refundPerUnit($line) * $qty;
            $items[] = ['item_id' => $itemId, 'item_qty' => (int) $qty];
        }
        if (count($sellers) > 1) {
            throw new GraphQlInputException(__('Items sold by different sellers need separate returns.'));
        }

        $type = ($input['type'] ?? '') === 'REPLACE' ? Vocabulary::TYPE_REPLACE : Vocabulary::TYPE_REFUND;
        [$reasonId, $otherReason] = $this->reason($input, $storeId);

        $refundType = 'full_amount';
        $customAmount = null;
        if ($type === Vocabulary::TYPE_REFUND && ($input['refund_amount_type'] ?? 'FULL') === 'CUSTOM') {
            $customAmount = isset($input['refund_custom_amount']) ? (float) $input['refund_custom_amount'] : 0.0;
            if (!($customAmount > 0)) {
                throw new GraphQlInputException(__('Enter the refund amount you are asking for.'));
            }
            if ($customAmount > $refundCap + 0.00001) {
                throw new GraphQlInputException(__(
                    'The refund can\'t be more than %1 %2 for these items.',
                    number_format(floor($refundCap * 100) / 100, 2, '.', ''),
                    (string) $order['order_currency_code']
                ));
            }
            $refundType = 'custom_amount';
        }

        $comment = trim((string) ($input['comment'] ?? ''));
        if ($comment === '') {
            throw new GraphQlInputException(__('Tell us about the return in the comment.'));
        }
        if (mb_strlen($comment) > self::MAX_COMMENT_LENGTH) {
            throw new GraphQlInputException(__('Keep the message under %1 characters.', self::MAX_COMMENT_LENGTH));
        }
        $tracking = trim((string) ($input['tracking_code'] ?? ''));
        if (mb_strlen($tracking) > self::MAX_TRACKING_LENGTH) {
            throw new GraphQlInputException(__('The tracking number is too long.'));
        }
        if ($tracking !== '' && !ReturnInput::isTrackingCode($tracking)) {
            //  The panels print the tracking code unescaped, as text and inside value="...".
            throw new GraphQlInputException(
                __('Use only letters, digits, spaces and . _ / # - in the tracking number.')
            );
        }

        return [
            'order' => $order,
            'items' => $items,
            'type' => $type,
            'reason' => $reasonId,
            'other_reason' => $otherReason,
            'package_opened' => !empty($input['package_opened']) ? 1 : 0,
            'comment' => $comment,
            'refund_amount_type' => $refundType,
            'refund_custom_amount' => $customAmount,
            'tracking_code' => $tracking,
        ];
    }

    /**
     * The customer's order with this number (the newest, should two store views share a number).
     *
     * @return array<string, mixed>|null
     */
    public function customerOrder(int $customerId, string $incrementId): ?array
    {
        $connection = $this->resource->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from(
                    $this->resource->getTableName('sales_order'),
                    ['entity_id', 'increment_id', 'status', 'state', 'customer_email', 'order_currency_code', 'store_id']
                )
                ->where('increment_id = ?', $incrementId)
                ->where('customer_id = ?', $customerId)
                ->order('entity_id DESC')
                ->limit(1)
        );

        return $row ?: null;
    }

    /**
     * @return array<int, float> order item id => requested quantity
     * @throws GraphQlInputException
     */
    private function requestedQuantities(mixed $items): array
    {
        if (!is_array($items) || !$items) {
            throw new GraphQlInputException(__('Choose at least one item to return.'));
        }
        if (count($items) > self::MAX_LINES_PER_RETURN) {
            throw new GraphQlInputException(__('A return can hold at most %1 lines.', self::MAX_LINES_PER_RETURN));
        }
        $out = [];
        foreach ($items as $item) {
            $itemId = (int) ($item['order_item_id'] ?? 0);
            if (isset($out[$itemId])) {
                throw new GraphQlInputException(__('Each item can be listed only once.'));
            }
            $out[$itemId] = (float) ($item['quantity'] ?? 0);
        }

        return $out;
    }

    /**
     * reason_id when given (an enabled reason), else other_reason when admin allows free text; a reason
     * is required when rma/general/enable_reasons is on.
     *
     * @param array<string, mixed> $input
     * @return array{0: int, 1: string} [reason id or 0, other reason or '']
     * @throws GraphQlInputException
     */
    private function reason(array $input, int $storeId): array
    {
        $reasonId = isset($input['reason_id']) ? (int) $input['reason_id'] : 0;
        $other = trim((string) ($input['other_reason'] ?? ''));
        if ($reasonId > 0) {
            $reasons = $this->labels->reasons($storeId);
            if (!isset($reasons[$reasonId]) || !$reasons[$reasonId]['active']) {
                throw new GraphQlInputException(__('Choose one of the listed return reasons.'));
            }

            return [$reasonId, ''];
        }
        if ($other !== '') {
            if (!$this->rmaConfig->allowOtherReasons()) {
                throw new GraphQlInputException(__('Choose one of the listed return reasons.'));
            }
            if (mb_strlen($other) > self::MAX_OTHER_REASON_LENGTH) {
                throw new GraphQlInputException(
                    __('Keep the reason under %1 characters.', self::MAX_OTHER_REASON_LENGTH)
                );
            }
            if (!ReturnInput::isSafeReason($other)) {
                //  Request::getReasonTitle() hands it to the panels, which print it unescaped.
                throw new GraphQlInputException(__('The reason can\'t contain < > or ".'));
            }

            return [0, $other];
        }
        if ($this->rmaConfig->enableReasons()) {
            throw new GraphQlInputException(__('Choose a reason for the return.'));
        }

        return [0, ''];
    }
}
