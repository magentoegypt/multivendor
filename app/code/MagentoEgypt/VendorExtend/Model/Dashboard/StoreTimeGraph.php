<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Model\Dashboard;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Seller dashboard charts in STORE time (TC66-QA02, 14zb93nvau4).
 *
 * Vnecoms' Graph grouped `created_at`, which is stored in UTC, with a bare DATE_FORMAT, and built its
 * windows (24h, 7d, 1m, 1y, 2y) from UTC dates. The store runs on Asia/Riyadh (UTC+3), so an order
 * placed between 00:00 and 03:00 Riyadh time was charted on the day before: seller V8S2's orders
 * of 28 Sep 02:24-02:26 were counted under 27 Sep, while the web panel's order list said 28 Sep.
 *
 * Same public methods, same point format ("Y-n-d", "Y-n-d H:00", "Y-n") and the same number of
 * points as before, so the app and the web dashboard read it unchanged. Now:
 *   - the window is taken in store time (today = the store's today) and converted to UTC for the
 *     WHERE on created_at;
 *   - each row is bucketed on CONVERT_TZ(created_at, '+00:00', <store offset>).
 * The offset is numeric (MySQL has no named time zone tables here: CONVERT_TZ(.., 'Asia/Riyadh')
 * returns NULL). Riyadh has no daylight saving, so one offset is exact; a store in a DST zone would
 * be out by an hour around the switch.
 *
 * Replaces the model everywhere it is used: the vendor API dashboard, the web seller dashboard and
 * the web sales-report graphs.
 */
class StoreTimeGraph extends \Vnecoms\VendorsDashboard\Model\Graph
{
    private const UNITS = [
        //         SQL label format       PHP label   step
        'hour'  => ['%Y-%c-%d %H:00', 'Y-n-d H:00', '+1 hour'],
        'day'   => ['%Y-%c-%d',       'Y-n-d',      '+1 day'],
        'month' => ['%Y-%c',          'Y-n',        '+1 month'],
    ];

    public function __construct(
        \Vnecoms\Credit\Model\Credit\TransactionFactory $transactionFactory,
        \Vnecoms\VendorsSales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        private readonly TimezoneInterface $timezone,
        private readonly ResourceConnection $resource,
        array $data = []
    ) {
        parent::__construct($transactionFactory, $orderFactory, $date, $data);
    }

    /* ---- orders (count) ---- */

    public function getOrdersDataLast24Hours($vendorId)
    {
        return $this->orders((int) $vendorId, 'order_num', ...$this->window('24h'));
    }

    public function getOrdersDataLast7Days($vendorId)
    {
        return $this->orders((int) $vendorId, 'order_num', ...$this->window('7d'));
    }

    public function getOrdersDataLastMonth($vendorId)
    {
        return $this->orders((int) $vendorId, 'order_num', ...$this->window('1m'));
    }

    public function getOrdersDataLastYear($vendorId)
    {
        return $this->orders((int) $vendorId, 'order_num', ...$this->window('1y'));
    }

    public function getOrdersDataLast2Years($vendorId)
    {
        return $this->orders((int) $vendorId, 'order_num', ...$this->window('2y'));
    }

    /* ---- orders (paid amount) ---- */

    public function getAmountsDataLast24Hours($vendorId)
    {
        return $this->orders((int) $vendorId, 'amount', ...$this->window('24h'));
    }

    public function getAmountsDataLast7Days($vendorId)
    {
        return $this->orders((int) $vendorId, 'amount', ...$this->window('7d'));
    }

    public function getAmountsDataLastMonth($vendorId)
    {
        return $this->orders((int) $vendorId, 'amount', ...$this->window('1m'));
    }

    public function getAmountsDataLastYear($vendorId)
    {
        return $this->orders((int) $vendorId, 'amount', ...$this->window('1y'));
    }

    public function getAmountsDataLast2Years($vendorId)
    {
        return $this->orders((int) $vendorId, 'amount', ...$this->window('2y'));
    }

    /* ---- credit transactions ---- */

    public function getTransactionsDataLast24Hours($customerId)
    {
        return $this->credit((int) $customerId, ...$this->window('24h'));
    }

    public function getTransactionDataLast7Days($customerId)
    {
        return $this->credit((int) $customerId, ...$this->window('7d'));
    }

    public function getTransactionDataLastMonth($customerId)
    {
        return $this->credit((int) $customerId, ...$this->window('1m'));
    }

    public function getTransactionDataLastYear($customerId)
    {
        return $this->credit((int) $customerId, ...$this->window('1y'));
    }

    public function getTransactionDataLast2Years($customerId)
    {
        return $this->credit((int) $customerId, ...$this->window('2y'));
    }

    /**
     * [from, to, unit] in store time; the same spans Vnecoms used, from the store's "now".
     *
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable, 2: string}
     */
    private function window(string $period): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone($this->timezone->getConfigTimezone()));
        if ($period === '24h') {
            return [$now->modify('-24 hours'), $now, 'hour'];
        }
        $today = $now->setTime(0, 0);
        $to = $now->setTime(23, 59, 59);

        return match ($period) {
            '7d' => [$today->modify('-7 days'), $to, 'day'],
            '1m' => [$today->modify('-30 days'), $to, 'day'],
            '1y' => [$today->modify('-1 year'), $to, 'month'],
            default => [$today->modify('-2 years'), $to, 'month'],
        };
    }

    /**
     * @return array<int, array{y: string, order_num: int|float, amount: int|float}>
     */
    private function orders(int $vendorId, string $key, \DateTimeImmutable $from, \DateTimeImmutable $to, string $unit): array
    {
        $value = $key === 'amount' ? 'SUM(base_total_paid)' : 'COUNT(entity_id)';
        $rows = $this->series('ves_vendor_sales_order', $value, ['vendor_id = ?' => $vendorId], $from, $to, $unit);

        $out = [];
        foreach ($rows as $label => $v) {
            $out[] = [
                'y' => $label,
                'order_num' => $key === 'order_num' ? (int) $v : 0,
                'amount' => $key === 'amount' ? (float) $v : 0,
            ];
        }

        return $out;
    }

    /**
     * @return array<int, array{y: string, received: float, spent: float}>
     */
    private function credit(int $customerId, \DateTimeImmutable $from, \DateTimeImmutable $to, string $unit): array
    {
        $where = $customerId ? ['customer_id = ?' => $customerId] : [];
        $received = $this->series('ves_store_credit_transaction', 'SUM(amount)', $where + ['amount > 0' => null], $from, $to, $unit);
        $spent = $this->series('ves_store_credit_transaction', 'SUM(amount)', $where + ['amount < 0' => null], $from, $to, $unit);

        $out = [];
        foreach ($received as $label => $v) {
            $out[] = ['y' => $label, 'received' => (float) $v, 'spent' => abs((float) ($spent[$label] ?? 0))];
        }

        return $out;
    }

    /**
     * One value per unit from $from to $to (store time), zero-filled, keyed by the store-time label.
     *
     * @param array<string, mixed> $where condition => bound value (null = no value)
     * @return array<string, float>
     */
    private function series(
        string $table,
        string $valueExpr,
        array $where,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        string $unit
    ): array {
        [$sqlFormat, $phpFormat, $step] = self::UNITS[$unit];
        $offset = $from->format('P');
        $utc = new \DateTimeZone('UTC');

        $conn = $this->resource->getConnection();
        $select = $conn->select()
            ->from(
                $this->resource->getTableName($table),
                [
                    'time' => new \Zend_Db_Expr(
                        "DATE_FORMAT(CONVERT_TZ(created_at, '+00:00', " . $conn->quote($offset) . "), '$sqlFormat')"
                    ),
                    'value' => new \Zend_Db_Expr($valueExpr),
                ]
            )
            ->where('created_at > ?', $from->setTimezone($utc)->format('Y-m-d H:i:s'))
            ->where('created_at < ?', $to->setTimezone($utc)->format('Y-m-d H:i:s'))
            ->group('time');
        foreach ($where as $condition => $value) {
            $value === null ? $select->where($condition) : $select->where($condition, $value);
        }
        $found = $conn->fetchPairs($select);

        $out = [];
        $pointer = match ($unit) {
            'hour' => $from->setTime((int) $from->format('H'), 0),
            'day' => $from->setTime(0, 0),
            default => $from->modify('first day of this month')->setTime(0, 0),
        };
        while ($pointer < $to) {
            $label = $pointer->format($phpFormat);
            $out[$label] = (float) ($found[$label] ?? 0);
            $pointer = $pointer->modify($step);
        }

        return $out;
    }
}
