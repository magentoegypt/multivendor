<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppAccount\Model\Resolver;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use MagentoEgypt\HubAppAccount\Model\Credit\CreditApplier;

/**
 * Mutation.hmRemoveStoreCredit — stop using store credit on the signed-in customer's cart.
 */
class RemoveStoreCredit implements ResolverInterface
{
    public function __construct(
        private readonly CreditApplier $applier
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
        $customerId = Caller::customerId($context);
        $cartId = trim((string) ($args['input']['cart_id'] ?? ''));
        if ($cartId === '') {
            throw new GraphQlInputException(__('Required parameter "cart_id" is missing'));
        }

        return ['cart' => ['model' => $this->applier->remove($cartId, $customerId, Caller::storeId($context))]];
    }
}
