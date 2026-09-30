<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Quote\Model\Quote;
use MagentoEgypt\HubAppAccount\Model\Credit\CreditAccountReader;

/**
 * Cart.hm_store_credit — credit applied, balance, most the cart can take, and whether the customer's
 * group may use credit; null for a guest cart. The cart itself was already authorised by the query
 * that returned it (cart, hmApplyStoreCredit, ...).
 */
class CartStoreCredit implements ResolverInterface
{
    public function __construct(
        private readonly CreditAccountReader $reader
    ) {
    }

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
        $cart = $value['model'] ?? null;

        return $cart instanceof Quote ? $this->reader->cartCredit($cart) : null;
    }
}
