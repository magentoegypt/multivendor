<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\AccountExtend\ViewModel;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Wishlist\Helper\Data as WishlistHelper;
use Psr\Log\LoggerInterface;

/**
 * The customer identity band and the Shopping Stats card.
 *
 * The Figma account screen opens with who you are — avatar, name, e-mail, an
 * order count and a wishlist count — and puts a small stats panel under the
 * sidebar nav. Magento's dashboard opens with two address boxes and has no
 * notion of lifetime spend at all.
 *
 * ONE QUERY FOR ALL FOUR FIGURES
 * ------------------------------
 * Order count, lifetime spend, orders this year and the average are a single
 * aggregate over sales_order, read straight off the connection. Loading an
 * order collection to count it would hydrate every row for four scalars, and
 * these blocks render on EVERY account route.
 *
 * WHAT COUNTS AS SPEND
 * --------------------
 * Cancelled and closed orders are excluded. A cancelled order is not money the
 * customer spent, and showing it as such on the one number a shopper might
 * check against their bank is the kind of wrong that gets noticed. `state` is
 * used rather than `status` because states are a fixed Magento vocabulary while
 * statuses are admin-editable, and this install already carries custom ones.
 *
 * Amounts are summed in the BASE currency and formatted in it, so a store with
 * a display currency different from its base does not silently mix the two.
 */
class AccountStats implements ArgumentInterface
{
    /** @var array<string, mixed>|null */
    private ?array $totals = null;

    public function __construct(
        private readonly CustomerSession $customerSession,
        private readonly ResourceConnection $resource,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly StoreManagerInterface $storeManager,
        private readonly WishlistHelper $wishlistHelper,
        private readonly TimezoneInterface $timezone,
        private readonly UrlInterface $url,
        private readonly LoggerInterface $logger
    ) {
    }

    public function isLoggedIn(): bool
    {
        return (bool) $this->customerSession->isLoggedIn();
    }

    public function getName(): string
    {
        $customer = $this->customerSession->getCustomer();

        return trim(($customer->getFirstname() ?? '') . ' ' . ($customer->getLastname() ?? ''));
    }

    public function getEmail(): string
    {
        return (string) $this->customerSession->getCustomer()->getEmail();
    }

    /**
     * Up to two initials for the avatar disc.
     *
     * `mb_substr`, not `substr`: most names on this store are Arabic, and
     * chopping a multi-byte string by bytes produces a replacement character
     * rather than a letter.
     */
    public function getInitials(): string
    {
        $customer = $this->customerSession->getCustomer();
        $parts = array_filter([
            trim((string) $customer->getFirstname()),
            trim((string) $customer->getLastname()),
        ]);

        if (!$parts) {
            return mb_strtoupper(mb_substr($this->getEmail(), 0, 1));
        }

        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_substr($part, 0, 1);
        }

        return mb_strtoupper($initials);
    }

    public function getOrderCount(): int
    {
        return (int) ($this->load()['orders'] ?? 0);
    }

    public function getOrdersThisYear(): int
    {
        return (int) ($this->load()['orders_this_year'] ?? 0);
    }

    public function getWishlistCount(): int
    {
        try {
            return (int) $this->wishlistHelper->getItemCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function getTotalSpentFormatted(): string
    {
        return $this->format((float) ($this->load()['total'] ?? 0));
    }

    public function getAverageOrderValueFormatted(): string
    {
        $data   = $this->load();
        $orders = (int) ($data['orders'] ?? 0);

        return $this->format($orders > 0 ? ((float) $data['total']) / $orders : 0.0);
    }

    public function getEditUrl(): string
    {
        return $this->url->getUrl('customer/account/edit');
    }

    /**
     * @return array<string, mixed>
     */
    private function load(): array
    {
        if ($this->totals !== null) {
            return $this->totals;
        }

        $this->totals = ['orders' => 0, 'total' => 0.0, 'orders_this_year' => 0];

        $customerId = (int) $this->customerSession->getCustomerId();
        if ($customerId <= 0) {
            return $this->totals;
        }

        try {
            $connection = $this->resource->getConnection();
            $table      = $this->resource->getTableName('sales_order');

            /*
             * The year boundary is taken in the STORE's timezone and converted
             * to UTC, because sales_order.created_at is UTC. Comparing against
             * a locally-formatted "Jan 1" would move the boundary by the offset
             * and mis-count orders placed in the first hours of the year.
             */
            $startOfYear = $this->timezone->date()->format('Y') . '-01-01 00:00:00';
            $startUtc    = $this->timezone->convertConfigTimeToUtc($startOfYear);

            $select = $connection->select()
                ->from($table, [
                    'orders' => new \Zend_Db_Expr('COUNT(*)'),
                    'total'  => new \Zend_Db_Expr('COALESCE(SUM(base_grand_total), 0)'),
                    'orders_this_year' => new \Zend_Db_Expr(
                        'SUM(CASE WHEN created_at >= ' . $connection->quote($startUtc) . ' THEN 1 ELSE 0 END)'
                    ),
                ])
                ->where('customer_id = ?', $customerId)
                ->where('state NOT IN (?)', ['canceled', 'closed']);

            $row = $connection->fetchRow($select);
            if ($row) {
                $this->totals = [
                    'orders' => (int) $row['orders'],
                    'total'  => (float) $row['total'],
                    'orders_this_year' => (int) $row['orders_this_year'],
                ];
            }
        } catch (\Throwable $e) {
            /*
             * A stats panel must not take the account dashboard down. Logged
             * rather than swallowed so it stays visible.
             */
            $this->logger->warning('AccountExtend: account stats unavailable: ' . $e->getMessage());
        }

        return $this->totals;
    }

    private function format(float $amount): string
    {
        try {
            $base = $this->storeManager->getStore()->getBaseCurrencyCode();

            return (string) $this->priceCurrency->format($amount, false, 2, null, $base);
        } catch (\Throwable $e) {
            return (string) $this->priceCurrency->format($amount, false);
        }
    }
}
