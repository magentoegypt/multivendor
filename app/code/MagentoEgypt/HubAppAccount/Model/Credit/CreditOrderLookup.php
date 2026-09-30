<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Credit;

use Magento\Framework\App\ResourceConnection;

/**
 * The SQL behind TransactionOrders: which order an invoice, a credit memo or a seller's share of an order
 * belongs to, and the order numbers of one customer's own orders. Reads only, one query per kind.
 */
class CreditOrderLookup
{
    /** Where each record Vnecoms names in additional_info keeps its order id. */
    private const SOURCES = [
        TransactionOrders::INVOICE => 'sales_invoice',
        TransactionOrders::CREDIT_MEMO => 'sales_creditmemo',
        TransactionOrders::SELLER_ORDER => 'ves_vendor_sales_order',
    ];

    public function __construct(
        private readonly ResourceConnection $resource
    ) {
    }

    /**
     * @param int[] $ids records of $kind (TransactionOrders::INVOICE, CREDIT_MEMO or SELLER_ORDER)
     * @return array<int, int> record id => order entity id
     */
    public function orderIds(string $kind, array $ids): array
    {
        $table = self::SOURCES[$kind] ?? null;
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
        if ($table === null || !$ids) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $tableName = $this->resource->getTableName($table);
        if (!$connection->isTableExists($tableName)) {
            return [];
        }
        $rows = $connection->fetchPairs(
            $connection->select()
                ->from($tableName, ['entity_id', 'order_id'])
                ->where('entity_id IN (?)', $ids)
        );
        $out = [];
        foreach ($rows as $id => $orderId) {
            $out[(int) $id] = (int) $orderId;
        }

        return $out;
    }

    /**
     * @param int[] $orderIds
     * @return array<int, string> order entity id => increment id, for the orders $customerId placed
     */
    public function ownOrderNumbers(array $orderIds, int $customerId): array
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds), static fn (int $id): bool => $id > 0)));
        if (!$orderIds || $customerId <= 0) {
            return [];
        }
        $connection = $this->resource->getConnection();
        $rows = $connection->fetchPairs(
            $connection->select()
                ->from($this->resource->getTableName('sales_order'), ['entity_id', 'increment_id'])
                ->where('entity_id IN (?)', $orderIds)
                ->where('customer_id = ?', $customerId)
        );
        $out = [];
        foreach ($rows as $id => $number) {
            if ((string) $number !== '') {
                $out[(int) $id] = (string) $number;
            }
        }

        return $out;
    }
}
