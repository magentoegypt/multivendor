<?php
/**
 * The seller order endpoints answer an item's row_total as Magento stores it.
 *
 * WHAT THE APP SHOWED ([CL036-TC90], 14zb93nvwph, 2026-10-05)
 * ----------------------------------------------------------
 * Order #3000000182, item test24 (SKU 1224): the admin order view says Subtotal
 * AED 3,500.00 and Row Total AED 3,740.00; the vendor app's item card said
 * Subtotal 3,400 and Row Total 3,640. sales_order_item holds row_total 3500,
 * tax 340, discount 100. GET /rest/en/V1/vendor/order/318, run through the real
 * REST pipeline, answered items[0].row_total 3400 and base_row_total 3400.
 *
 * WHY
 * ---
 * Not our code: Magento 2.4.x registers Magento\Sales\Model\Order\Webapi\
 * ChangeOutputArray as a REST/SOAP output processor for every
 * Magento\Sales\Model\Order\Item. It rewrites row_total and base_row_total to the
 * admin column's "total amount", i.e. row_total MINUS the discount, and
 * row_total_incl_tax to the discounted total. The app then takes the discount off
 * a second time (3400 + 340 - 100 = 3640).
 *
 * WHAT THIS DOES
 * --------------
 * Only for the seller order endpoints the vendor app reads
 * (GET /V1/vendor/order/:orderId and GET /V1/vendors/order): row_total and
 * base_row_total go back to the stored values, so the app shows Subtotal 3,500
 * and Row Total 3,740 like the admin. row_total_incl_tax keeps core's (discounted)
 * figure. Every other REST/SOAP client (/V1/orders, integrations) keeps core's
 * output untouched.
 */
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\Webapi;

use Magento\Framework\App\RequestInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\Order\Webapi\ChangeOutputArray;

class SellerOrderItemRowTotal
{
    /** The seller order routes, after the store code: /V1/vendor/order/:id and /V1/vendors/order. */
    private const SELLER_ORDER_PATH = '#/V1/(vendor/order/\d+|vendors/order)/?$#';

    public function __construct(
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @param ChangeOutputArray $subject
     * @param array<string, mixed> $result
     * @param OrderItemInterface $dataObject
     * @return array<string, mixed>
     */
    public function afterExecute(ChangeOutputArray $subject, array $result, OrderItemInterface $dataObject): array
    {
        if (!$this->isSellerOrderRequest()) {
            return $result;
        }

        $result[OrderItemInterface::ROW_TOTAL] = (float) $dataObject->getRowTotal();
        $result[OrderItemInterface::BASE_ROW_TOTAL] = (float) $dataObject->getBaseRowTotal();

        return $result;
    }

    private function isSellerOrderRequest(): bool
    {
        $path = (string) $this->request->getPathInfo();

        return (bool) preg_match(self::SELLER_ORDER_PATH, $path);
    }
}
