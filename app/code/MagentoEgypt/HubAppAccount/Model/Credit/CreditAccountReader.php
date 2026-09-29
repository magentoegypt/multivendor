<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Credit;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use MagentoEgypt\HubApp\Api\StorefrontEmulationInterface;
use MagentoEgypt\HubAppAccount\Model\Paging;
use Psr\Log\LoggerInterface;
use Vnecoms\Credit\Helper\Data as CreditHelper;
use Vnecoms\Credit\Model\Processor;

/**
 * A customer's store credit (Vnecoms Credit): balance, transactions, whether they may spend it, and
 * the credit on a cart.
 *
 * Reads only. The balance is ves_store_credit.credit, in the website's base currency; a customer with
 * no account row has 0 (Credit::loadByCustomerId would create the row, which a query must not do).
 * Seller commissions (VendorExtend ItemCommission) share this ledger, so a customer who is also a
 * seller sees those transactions here, as on the website's "My Credit" page.
 *
 * Spending follows the website, not the REST shortcut: Vnecoms\Credit\Helper\Data::canUseCredit() with
 * the customer's group against credit/general/credit_group. With that setting empty (no default) nobody
 * may spend credit: the website hides the cart form, and the app is told can_use false.
 */
class CreditAccountReader
{
    /** @var array<int, bool> customer id => may spend credit */
    private array $canUse = [];

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly CreditHelper $creditHelper,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly Processor $processor,
        private readonly StorefrontEmulationInterface $emulation,
        private readonly StoreManagerInterface $storeManager,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Balance in the base currency.
     */
    public function balance(int $customerId): float
    {
        $connection = $this->resource->getConnection();

        return (float) $connection->fetchOne(
            $connection->select()
                ->from($this->resource->getTableName('ves_store_credit'), ['credit'])
                ->where('customer_id = ?', $customerId)
                ->limit(1)
        );
    }

    /**
     * May this customer spend credit at checkout (the website's group rule)?
     */
    public function canUse(int $customerId): bool
    {
        if (!isset($this->canUse[$customerId])) {
            try {
                $groupId = (int) $this->customerRepository->getById($customerId)->getGroupId();
                $this->canUse[$customerId] = (bool) $this->creditHelper->canUseCredit($groupId);
            } catch (\Throwable $e) {
                $this->logger->warning('HubAppAccount: credit group check failed: ' . $e->getMessage());
                $this->canUse[$customerId] = false;
            }
        }

        return $this->canUse[$customerId];
    }

    /**
     * HmStoreCreditAccount: balance and one page of transactions, newest first.
     *
     * @return array<string, mixed>
     */
    public function account(int $customerId, int $storeId, Paging $paging): array
    {
        $currency = $this->baseCurrency($storeId);
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('ves_store_credit_transaction');

        $total = (int) $connection->fetchOne(
            $connection->select()->from($table, ['COUNT(*)'])->where('customer_id = ?', $customerId)
        );
        $rows = $total === 0 ? [] : $connection->fetchAll(
            $connection->select()
                ->from($table, ['transaction_id', 'type', 'amount', 'balance', 'description', 'created_at'])
                ->where('customer_id = ?', $customerId)
                ->order(['created_at DESC', 'transaction_id DESC'])
                ->limit($paging->pageSize, $paging->offset())
        );
        $labels = $this->typeLabels(array_column($rows, 'type'), $storeId);

        $transactions = [];
        foreach ($rows as $row) {
            $type = (string) $row['type'];
            $description = $this->plain((string) $row['description']);
            $transactions[] = [
                'id' => (int) $row['transaction_id'],
                'type' => $type,
                'type_label' => $labels[$type] ?? $type,
                'amount' => $this->money((float) $row['amount'], $currency),
                'balance_after' => $this->money((float) $row['balance'], $currency),
                'description' => $description !== '' ? $description : null,
                'created_at' => $this->utc((string) $row['created_at']),
            ];
        }

        return [
            'balance' => $this->money($this->balance($customerId), $currency),
            'can_use_at_checkout' => $this->canUse($customerId),
            'transactions' => $transactions,
            'total_count' => $total,
            'page_info' => $paging->pageInfo($total),
        ];
    }

    /**
     * Cart.hm_store_credit: null for a guest cart; amounts in the cart's currency.
     *
     * max_applicable repeats the credit total collector (Vnecoms\Credit\Model\Quote\Credit): the
     * balance, capped at subtotal after discount + shipping + tax of the cart's address.
     *
     * @return array<string, mixed>|null
     */
    public function cartCredit(Quote $quote): ?array
    {
        $customerId = (int) $quote->getCustomerId();
        if ($customerId <= 0) {
            return null;
        }
        $rate = (float) $quote->getBaseToQuoteRate();
        $rate = $rate > 0 ? $rate : 1.0;
        $currency = (string) $quote->getQuoteCurrencyCode();
        if ($currency === '') {
            $currency = $this->baseCurrency((int) $quote->getStoreId());
        }

        $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
        $orderTotal = max(
            0.0,
            (float) $address->getBaseSubtotalWithDiscount()
            + (float) $address->getBaseShippingAmount()
            + (float) $address->getBaseTaxAmount()
        );
        $balance = $this->balance($customerId);

        return [
            'applied' => $this->money(abs((float) $quote->getCreditAmount()), $currency),
            'balance' => $this->money($balance * $rate, $currency),
            'max_applicable' => $this->money(min($balance, $orderTotal) * $rate, $currency),
            'can_use' => $this->canUse($customerId),
        ];
    }

    /**
     * Localised titles of the credit processors (Vnecoms\Credit\Model\Processor), with the storefront
     * theme's translations; an unknown type reads as its humanised code.
     *
     * @param string[] $types
     * @return array<string, string>
     */
    private function typeLabels(array $types, int $storeId): array
    {
        $types = array_values(array_unique(array_filter(array_map('strval', $types))));
        if (!$types) {
            return [];
        }
        $processors = $this->processor->getProcessors();

        return $this->emulation->run($storeId, static function () use ($types, $processors): array {
            $out = [];
            foreach ($types as $type) {
                $label = '';
                $processor = $processors[$type] ?? null;
                if (is_object($processor) && method_exists($processor, 'getTitle')) {
                    try {
                        $label = trim((string) $processor->getTitle());
                    } catch (\Throwable $e) {
                        $label = '';
                    }
                }
                $out[$type] = $label !== '' ? $label : (string) __(ucwords(str_replace('_', ' ', $type)));
            }

            return $out;
        });
    }

    private function baseCurrency(int $storeId): string
    {
        try {
            return (string) $this->storeManager->getStore($storeId)->getBaseCurrencyCode();
        } catch (\Throwable $e) {
            return (string) $this->storeManager->getStore()->getBaseCurrencyCode();
        }
    }

    /**
     * @return array{value: float, currency: string}
     */
    private function money(float $value, string $currency): array
    {
        return ['value' => round($value, 2), 'currency' => $currency];
    }

    private function plain(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * A UTC MySQL timestamp (Magento's connections run in UTC) as ISO-8601 with "Z".
     */
    private function utc(string $value): string
    {
        $value = trim($value);
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return '';
        }
        try {
            return (new \DateTimeImmutable($value, new \DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z');
        } catch (\Exception $e) {
            return '';
        }
    }
}
