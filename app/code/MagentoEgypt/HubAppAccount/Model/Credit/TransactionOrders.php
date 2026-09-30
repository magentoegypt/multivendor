<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Credit;

use Psr\Log\LoggerInterface;

/**
 * HmStoreCreditTransaction.order_number: the customer's own order a credit transaction records.
 *
 * Vnecoms records it in ves_store_credit_transaction.additional_info as "<record>|<id>", the record the
 * processor was given (Vnecoms\Credit\Model\Processor\*, overridden by VendorExtend\Model\Processor\* and
 * VendorExtend\Model\CreditProcessor\*):
 *   - spend_credit, refund_spent_credit       order|<sales_order id>
 *   - buy_credit                              invoice|<sales_invoice id>
 *   - refund_by_credit                        creditmemo|<sales_creditmemo id>
 *   - vendor_refund_spent_credit              vendor_order|<ves_vendor_sales_order id>
 * The admin's own adjustments record nothing. A seller's ledger lines (order_payment, item_commission and
 * their refunds, withdrawals) name invoices, items and credit memos of OTHER customers' orders; they stay
 * without a number because only an order the customer placed is ever answered (sales_order.customer_id),
 * whatever the line says. Returns (Vnecoms RMA) are never recorded in the ledger, so there is no return
 * reference to give.
 *
 * Lookups that fail leave the numbers out: the transactions still answer.
 */
class TransactionOrders
{
    public const ORDER = 'order';
    public const INVOICE = 'invoice';
    public const CREDIT_MEMO = 'creditmemo';
    public const SELLER_ORDER = 'vendor_order';

    public function __construct(
        private readonly CreditOrderLookup $lookup,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * The record additional_info names, when it is one that leads to an order. Pure.
     *
     * @return array{kind: string, id: int}|null
     */
    public static function parse(?string $additionalInfo): ?array
    {
        $parts = explode('|', trim((string) $additionalInfo));
        if (count($parts) !== 2) {
            return null;
        }
        $kind = strtolower(trim($parts[0]));
        $id = trim($parts[1]);
        if (!in_array($kind, [self::ORDER, self::INVOICE, self::CREDIT_MEMO, self::SELLER_ORDER], true)
            || preg_match('/^[1-9]\d{0,9}$/', $id) !== 1
        ) {
            return null;
        }

        return ['kind' => $kind, 'id' => (int) $id];
    }

    /**
     * @param array<int, string|null> $additionalInfo transaction id => additional_info
     * @return array<int, string> transaction id => number of the order it records, for $customerId's own
     *     orders only
     */
    public function numbers(array $additionalInfo, int $customerId): array
    {
        $records = [];
        foreach ($additionalInfo as $transactionId => $info) {
            $record = self::parse($info);
            if ($record !== null) {
                $records[(int) $transactionId] = $record;
            }
        }
        if (!$records) {
            return [];
        }

        try {
            $idsByKind = [];
            foreach ($records as $record) {
                $idsByKind[$record['kind']][] = $record['id'];
            }
            $orderIdByRecord = [];
            foreach ($idsByKind as $kind => $ids) {
                $orderIdByRecord[$kind] = $kind === self::ORDER
                    ? array_combine($ids, $ids)
                    : $this->lookup->orderIds($kind, $ids);
            }

            $orderIds = [];
            foreach ($records as $transactionId => $record) {
                $orderId = $orderIdByRecord[$record['kind']][$record['id']] ?? null;
                if ($orderId) {
                    $orderIds[$transactionId] = (int) $orderId;
                }
            }
            $numbers = $this->lookup->ownOrderNumbers(array_values($orderIds), $customerId);
        } catch (\Throwable $e) {
            $this->logger->warning('HubAppAccount: credit transaction orders not read: ' . $e->getMessage());

            return [];
        }

        $out = [];
        foreach ($orderIds as $transactionId => $orderId) {
            if (isset($numbers[$orderId])) {
                $out[$transactionId] = $numbers[$orderId];
            }
        }

        return $out;
    }
}
