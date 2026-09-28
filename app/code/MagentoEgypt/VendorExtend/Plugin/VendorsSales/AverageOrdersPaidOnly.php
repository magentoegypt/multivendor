<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\VendorsSales;

use Vnecoms\VendorsSales\Model\ResourceModel\Order;

/**
 * Seller "Average Orders" = lifetime sales / the orders that make up lifetime sales.
 *
 * Vnecoms computes SUM(base_total_paid) / COUNT(every vendor order). Lifetime Sales is
 * SUM(base_total_paid), so pending, unpaid orders added nothing to the numerator yet still counted
 * in the denominator. Seller V8S2: 26,000 over 90 orders = 288.89, where 26,000 over its 36 paid
 * orders = 722.22 (TC66-QA01, 2026-09-28). Both the seller panel dashboard and the vendor app's
 * dashboard API (average_orders) read this method.
 *
 * Counts only orders with money paid (base_total_paid > 0), which is the set Lifetime Sales sums.
 */
class AverageOrdersPaidOnly
{
    public function aroundGetAverageOrders(Order $subject, callable $proceed, $vendorId)
    {
        $connection = $subject->getConnection();
        $row = $connection->fetchRow(
            $connection->select()
                ->from(
                    $subject->getTable('ves_vendor_sales_order'),
                    ['paid' => 'SUM(base_total_paid)', 'orders' => 'COUNT(entity_id)']
                )
                ->where('vendor_id = ?', (int) $vendorId)
                ->where('base_total_paid > 0')
        );

        if (empty($row['paid']) || empty($row['orders'])) {
            return 0;
        }

        return $row['paid'] / $row['orders'];
    }
}
