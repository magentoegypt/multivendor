<?php
declare(strict_types=1);

namespace MagentoEgypt\VendorExtend\Plugin\Sales;

use Magento\Sales\Block\Adminhtml\Items\AbstractItems;
use Magento\Sales\Model\Order\Item as OrderItem;

/**
 * "Discount Amount" on an order's item rows also shows the special/catalog price reduction (CL036-TC28, 86d4bjum9).
 *
 * A vendor special price (2,000 → 1,900) is a price, not a discount line: Magento sells the item at 1,900 and stores
 * discount_amount = 0, so the column showed 0.00 while a cart-rule discount showed correctly. Every order item view
 * (admin order, admin Marketplace order, seller panel) prints that column through displayPriceAttribute('discount_amount'),
 * so the reduction is added here once:
 *
 *     shown discount = cart-rule discount + (original price − sold price) × qty ordered
 *
 * Display only. sales_order_item.discount_amount, the order totals, tax, invoices and credit memos are unchanged
 * (writing the reduction into discount_amount would count it twice). Only order items carry an original price, so
 * invoice and credit-memo rows are not affected.
 */
class OrderItemDiscountColumn
{
    /**
     * @param AbstractItems $subject
     * @param callable $proceed
     * @param string $code
     * @param bool $strong
     * @param string $separator
     * @return string
     */
    public function aroundDisplayPriceAttribute(
        AbstractItems $subject,
        callable $proceed,
        $code,
        $strong = false,
        $separator = '<br />'
    ) {
        $item = $subject->getPriceDataObject();
        if ($code !== 'discount_amount' || !$item instanceof OrderItem) {
            return $proceed($code, $strong, $separator);
        }

        $qty = (float) $item->getQtyOrdered();
        $reduction = max(0.0, ((float) $item->getOriginalPrice() - (float) $item->getPrice()) * $qty);
        $baseReduction = max(0.0, ((float) $item->getBaseOriginalPrice() - (float) $item->getBasePrice()) * $qty);
        if ($reduction <= 0.0 && $baseReduction <= 0.0) {
            return $proceed($code, $strong, $separator);
        }

        return $subject->displayPrices(
            (float) $item->getBaseDiscountAmount() + $baseReduction,
            (float) $item->getDiscountAmount() + $reduction,
            $strong,
            $separator
        );
    }
}
