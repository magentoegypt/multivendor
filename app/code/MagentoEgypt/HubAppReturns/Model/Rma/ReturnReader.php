<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Rma;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use MagentoEgypt\HubApp\Api\MediaUrlInterface;
use Vnecoms\VendorsRMA\Model\Request;
use Vnecoms\VendorsRMA\Model\RequestFactory;

/**
 * A customer's returns as HmReturnSummary (list) and HmReturn (detail), read the way the website's
 * "My Returns" pages read them:
 *
 *  - the list is the customer's requests in the store view's website, newest first
 *    (Vnecoms\RMA\Block\Frontend\Customer\ListRma), each with its status code (to colour it), its
 *    refund amount and its first line (to picture it), batched for the page;
 *  - a return is shown only to the customer it belongs to (RmaViewAuthorization::canView), and opening
 *    it marks it read for the customer (Controller\Customer\View sets is_customer_read = 1), which is
 *    what HmReturnSummary.has_unread_reply reports;
 *  - staff never appear by name: history and messages carry a role, and "Hub Market" for staff.
 */
class ReturnReader
{
    /** HmReturnMessage.author_name for staff; Latin in both locales, like the storefront header. */
    public const STAFF_NAME = 'Hub Market';

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly RequestFactory $requestFactory,
        private readonly LabelReader $labels,
        private readonly OrderLineReader $lines,
        private readonly MediaUrlInterface $mediaUrl
    ) {
    }

    /**
     * @return array{total: int, items: array<int, array<string, mixed>>} HmReturnSummary rows
     */
    public function page(int $customerId, int $websiteId, int $storeId, Paging $paging): array
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('ves_rma_request_entity');
        $where = static function ($select) use ($customerId, $websiteId) {
            return $select->where('customer_id = ?', $customerId)->where('website_id = ?', $websiteId);
        };

        $total = (int) $connection->fetchOne($where($connection->select()->from($table, ['COUNT(*)'])));
        if ($total === 0) {
            return ['total' => 0, 'items' => []];
        }
        $rows = $connection->fetchAll(
            $where($connection->select()->from($table, [
                'entity_id', 'increment_id', 'order_incremental_id', 'created_at', 'updated_at', 'state', 'status',
                'type', 'vendor_id', 'is_customer_read', 'refund_amount',
            ]))
                ->order(['created_at DESC', 'entity_id DESC'])
                ->limit($paging->pageSize, $paging->offset())
        );
        if (!$rows) {
            return ['total' => $total, 'items' => []];
        }

        //  Every line of the page's returns, in the order filed: the count, and the first to show.
        $ids = array_map('intval', array_column($rows, 'entity_id'));
        $itemCounts = [];
        $firstLines = [];
        foreach ($connection->fetchAll(
            $connection->select()
                ->from($this->resource->getTableName('ves_rma_request_item'), ['request_id', 'order_item_id'])
                ->where('request_id IN (?)', $ids)
                ->order(['request_id ASC', 'item_id ASC'])
        ) as $item) {
            $requestId = (int) $item['request_id'];
            $itemCounts[$requestId] = ($itemCounts[$requestId] ?? 0) + 1;
            $firstLines[$requestId] ??= (int) $item['order_item_id'];
        }
        $lines = $this->lines->linesById(array_values($firstLines));
        $shown = $this->lines->present($lines, $storeId);
        $statuses = $this->labels->statuses($storeId);
        $sellers = $this->lines->sellerSummaries(array_column($rows, 'vendor_id'), $storeId);
        $currencies = $this->orderCurrencies($customerId, array_column($rows, 'order_incremental_id'));

        $items = [];
        foreach ($rows as $row) {
            $requestId = (int) $row['entity_id'];
            $status = $statuses[(int) $row['status']] ?? ['code' => '', 'label' => 'N/A'];
            $type = (string) $row['type'];
            $firstLine = $lines[$firstLines[$requestId] ?? 0] ?? null;
            $refundAmount = $row['refund_amount'] ?? null;
            $items[] = [
                'id' => $requestId,
                'number' => (string) $row['increment_id'],
                'order_number' => (string) $row['order_incremental_id'],
                'created_at' => Vocabulary::utc((string) $row['created_at']),
                'updated_at' => Vocabulary::utc((string) $row['updated_at']),
                'state' => Vocabulary::state((string) $row['state'], $status['code']),
                'status_code' => $status['code'],
                'status_label' => $status['label'],
                'type' => Vocabulary::type($type),
                'item_count' => $itemCounts[$requestId] ?? 0,
                'seller' => $sellers[(int) $row['vendor_id']] ?? null,
                'has_unread_reply' => (int) $row['is_customer_read'] === 0,
                //  As HmReturn.refund_amount: refunds only, once an amount is stored.
                'refund_amount' => $type === Vocabulary::TYPE_REFUND && $refundAmount !== null && $refundAmount !== ''
                    ? Vocabulary::money((float) $refundAmount, $currencies[(string) $row['order_incremental_id']] ?? '')
                    : null,
                'first_item' => $firstLine === null ? null : [
                    'name' => $shown[(int) $firstLine['item_id']]['name'] ?? (string) $firstLine['name'],
                    'thumbnail' => $shown[(int) $firstLine['item_id']]['image_url'] ?? null,
                ],
            ];
        }

        return ['total' => $total, 'items' => $items];
    }

    /**
     * The order currency of each of the customer's orders among these numbers (the newest order,
     * should two store views share a number, as EligibilityService::customerOrder picks it).
     *
     * @param array<int, string> $orderNumbers
     * @return array<string, string> order number => currency code
     */
    private function orderCurrencies(int $customerId, array $orderNumbers): array
    {
        $orderNumbers = array_values(array_unique(array_filter(array_map('strval', $orderNumbers))));
        if (!$orderNumbers) {
            return [];
        }
        $connection = $this->resource->getConnection();

        return array_map('strval', $connection->fetchPairs(
            $connection->select()
                ->from($this->resource->getTableName('sales_order'), ['increment_id', 'order_currency_code'])
                ->where('customer_id = ?', $customerId)
                ->where('increment_id IN (?)', $orderNumbers)
                ->order('entity_id ASC')
        ));
    }

    /**
     * The customer's own request, loaded (full EAV entity, tracking code included), else null.
     */
    public function load(int $customerId, int $requestId): ?Request
    {
        if ($requestId <= 0) {
            return null;
        }
        $request = $this->requestFactory->create();
        $request->load($requestId);
        if (!$request->getId() || (int) $request->getCustomerId() !== $customerId || $customerId <= 0) {
            return null;
        }

        return $request;
    }

    /**
     * HmReturn for the customer's own request, else null; marks it read for the customer.
     *
     * @return array<string, mixed>|null
     */
    public function detail(int $customerId, int $requestId, int $storeId): ?array
    {
        $request = $this->load($customerId, $requestId);
        if ($request === null) {
            return null;
        }
        $this->markRead($customerId, $requestId, (int) $request->getData('is_customer_read'));

        $connection = $this->resource->getConnection();
        $statuses = $this->labels->statuses($storeId);
        $status = $statuses[(int) $request->getData('status')] ?? ['code' => '', 'label' => 'N/A'];

        $requestItems = $connection->fetchAll(
            $connection->select()
                ->from($this->resource->getTableName('ves_rma_request_item'), ['order_item_id', 'qty'])
                ->where('request_id = ?', $requestId)
                ->order('item_id ASC')
        );
        $lines = $this->lines->linesById(array_column($requestItems, 'order_item_id'));
        $shown = $this->lines->present($lines, $storeId);
        $vendorId = (int) $request->getData('vendor_id');
        $seller = $this->lines->sellerSummaries([$vendorId], $storeId)[$vendorId] ?? null;

        $items = [];
        $fullRefund = 0.0;
        foreach ($requestItems as $row) {
            $itemId = (int) $row['order_item_id'];
            $qty = (float) $row['qty'];
            $line = $lines[$itemId] ?? null;
            if ($line !== null) {
                $fullRefund += $this->lines->refundPerUnit($line) * $qty;
            }
            $items[] = [
                'order_item_id' => $itemId,
                'sku' => $shown[$itemId]['sku'] ?? (string) ($line['sku'] ?? ''),
                'name' => $shown[$itemId]['name'] ?? (string) ($line['name'] ?? ''),
                'image_url' => $shown[$itemId]['image_url'] ?? null,
                'quantity' => $qty,
            ];
        }

        $type = (string) $request->getData('type');
        $reasonId = (int) $request->getData('reason');
        $reasons = $this->labels->reasons($storeId);
        $otherReason = trim((string) $request->getData('other_reason'));
        $trackingCode = trim((string) $request->getData('tracking_code'));
        $refundAmount = $request->getData('refund_amount');
        $refund = $this->refund($type, $refundAmount, $fullRefund, $customerId, (string) $request->getData('order_incremental_id'));

        return [
            'id' => (int) $request->getId(),
            'number' => (string) $request->getData('increment_id'),
            'order_number' => (string) $request->getData('order_incremental_id'),
            'created_at' => Vocabulary::utc((string) $request->getData('created_at')),
            'updated_at' => Vocabulary::utc((string) $request->getData('updated_at')),
            'state' => Vocabulary::state((string) $request->getData('state'), $status['code']),
            'status_code' => $status['code'],
            'status_label' => $status['label'],
            'type' => Vocabulary::type($type),
            'reason' => $reasonId > 0
                ? ['id' => $reasonId, 'label' => $reasons[$reasonId]['label'] ?? 'N/A']
                : null,
            'other_reason' => $otherReason !== '' ? $otherReason : null,
            'package_opened' => (int) $request->getData('package_opened') === 1,
            'refund_amount_type' => $refund['type'],
            'refund_amount' => $refund['amount'],
            'tracking_code' => $trackingCode !== '' ? $trackingCode : null,
            'seller' => $seller,
            'items' => $items,
            'history' => $this->history($requestId, $statuses),
            'messages' => $this->messages($requestId, $storeId, (string) $request->getData('customer_name'), $seller),
        ];
    }

    /**
     * Status history, oldest first.
     *
     * @param array<int, array{code: string, label: string}> $statuses
     * @return array<int, array<string, string>>
     */
    private function history(int $requestId, array $statuses): array
    {
        $connection = $this->resource->getConnection();
        $rows = $connection->fetchAll(
            $connection->select()
                ->from($this->resource->getTableName('ves_rma_request_history'), ['status', 'type', 'created_time'])
                ->where('request_id = ?', $requestId)
                ->order(['created_time ASC', 'history_id ASC'])
        );
        $out = [];
        foreach ($rows as $row) {
            $status = $statuses[(int) $row['status']] ?? ['code' => '', 'label' => 'N/A'];
            $out[] = [
                'status_code' => $status['code'],
                'status_label' => $status['label'],
                'changed_by' => Vocabulary::historyActor((string) $row['type']),
                'created_at' => Vocabulary::utc((string) $row['created_time']),
            ];
        }

        return $out;
    }

    /**
     * The thread, oldest first, sanitised; staff as "Hub Market", the seller by its display name.
     *
     * @param array<string, mixed>|null $seller
     * @return array<int, array<string, mixed>>
     */
    private function messages(int $requestId, int $storeId, string $customerName, ?array $seller): array
    {
        $connection = $this->resource->getConnection();
        $rows = $connection->fetchAll(
            $connection->select()
                ->from(
                    $this->resource->getTableName('ves_rma_request_message'),
                    ['message_id', 'message', 'attachment', 'type', 'from', 'created_at']
                )
                ->where('request_id = ?', $requestId)
                ->order(['created_at ASC', 'message_id ASC'])
        );
        $out = [];
        foreach ($rows as $row) {
            $author = Vocabulary::messageActor((string) $row['type']);
            $from = trim(strip_tags((string) $row['from']));
            if ($author === 'CUSTOMER') {
                $name = $from !== '' ? $from : $customerName;
            } elseif ($author === 'SELLER') {
                $name = (string) ($seller['name'] ?? '') !== '' ? (string) $seller['name'] : $from;
            } else {
                $name = self::STAFF_NAME;
            }
            $attachments = $this->attachments((string) $row['attachment'], $storeId);
            $out[] = [
                'id' => (int) $row['message_id'],
                'author' => $author,
                'author_name' => $name !== '' ? $name : self::STAFF_NAME,
                'body_html' => MessageBody::toHtml((string) $row['message']),
                'body_text' => MessageBody::toText((string) $row['message']),
                'attachment_urls' => array_column($attachments, 'url'),
                'attachments' => $attachments,
                'created_at' => Vocabulary::utc((string) $row['created_at']),
            ];
        }

        return $out;
    }

    /**
     * A message's (or an escalation's) files, in the order stored: name and URL. They live in
     * pub/media/rma/request (Vnecoms\RMA\Model\Message::getAttachmentUrls).
     *
     * @return array<int, array{name: string, url: string}>
     */
    private function attachments(string $attachment, int $storeId): array
    {
        $out = [];
        foreach (explode(',', $attachment) as $file) {
            $file = trim($file);
            if ($file === '' || str_contains($file, '..')) {
                continue;
            }
            $path = implode('/', array_map('rawurlencode', explode('/', ltrim($file, '/'))));
            $url = $this->mediaUrl->media(Attachments::DIR . '/' . $path, $storeId);
            if ($url !== null) {
                $out[] = ['name' => basename(str_replace('\\', '/', $file)), 'url' => $url];
            }
        }

        return $out;
    }

    /**
     * Refund mode and amount. Vnecoms stores only the amount (refund_amount, in the order currency);
     * FULL/CUSTOM is read back by comparing it with the full amount of the lines, which is what
     * "full_amount" stores at filing time. A seller or admin changing the amount makes it CUSTOM.
     *
     * @return array{type: ?string, amount: ?array{value: float, currency: string}}
     */
    private function refund(string $type, mixed $amount, float $fullRefund, int $customerId, string $orderNumber): array
    {
        if ($type !== Vocabulary::TYPE_REFUND || $amount === null || $amount === '') {
            return ['type' => null, 'amount' => null];
        }
        $amount = (float) $amount;
        $connection = $this->resource->getConnection();
        $currency = (string) $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('sales_order'), ['order_currency_code'])
                ->where('increment_id = ?', $orderNumber)
                ->where('customer_id = ?', $customerId)
                ->order('entity_id DESC')
                ->limit(1)
        );

        return [
            'type' => abs($amount - $fullRefund) < 0.005 ? 'FULL' : 'CUSTOM',
            'amount' => $currency !== '' ? ['value' => round($amount, 4), 'currency' => $currency] : null,
        ];
    }

    /**
     * is_customer_read = 1 without touching updated_at (the column auto-updates unless it is set).
     */
    private function markRead(int $customerId, int $requestId, int $isRead): void
    {
        if ($isRead === 1) {
            return;
        }
        $connection = $this->resource->getConnection();
        $connection->update(
            $this->resource->getTableName('ves_rma_request_entity'),
            ['is_customer_read' => 1, 'updated_at' => new Expression('updated_at')],
            ['entity_id = ?' => $requestId, 'customer_id = ?' => $customerId]
        );
    }
}
