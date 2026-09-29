<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Sales\Model\Order;

/**
 * OrderTotal.hm_store_credit — store credit used on the order (sales_order.credit_amount, in the order
 * currency), as a positive amount like OrderTotal.discounts; null when none was used.
 *
 * Vnecoms stores it negative (the total collector's -used credit, copied to the order).
 */
class OrderStoreCredit implements ResolverInterface
{
    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        ?array $value = null,
        ?array $args = null
    ) {
        $order = $value['model'] ?? null;
        if (!$order instanceof Order) {
            return null;
        }
        $used = abs((float) $order->getData('credit_amount'));
        if ($used < 0.00001) {
            return null;
        }

        return ['value' => round($used, 2), 'currency' => (string) $order->getOrderCurrencyCode()];
    }
}
