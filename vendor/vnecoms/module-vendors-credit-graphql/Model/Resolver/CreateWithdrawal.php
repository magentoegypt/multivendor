<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsCreditGraphQl\Model\Resolver;

use Vnecoms\VendorsCreditGraphQl\Model\Credit\CreateWithdrawal as CreateWithdrawalAction;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Customers field resolver, used for GraphQL request processing.
 */
class CreateWithdrawal implements ResolverInterface
{
    /**
     * @var CreateWithdrawalAction
     */
    private $createWithdrawalAction;

    /**
     * CreateWithdrawal constructor.
     * @param CreateWithdrawalAction $createWithdrawalAction
     */
    public function __construct(
        CreateWithdrawalAction $createWithdrawalAction
    ) {
        $this->createWithdrawalAction = $createWithdrawalAction;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        /** @var ContextInterface $context */
        if (false === $context->getExtensionAttributes()->getIsCustomer()) {
            throw new GraphQlAuthorizationException(__('The current vendor isn\'t authorized.'));
        }

        if (!$args['amount'] || !$args['method']) {
            throw new GraphQlInputException(__('Amount or Method must be not null'));
        }
        return $this->createWithdrawalAction->execute($context, $args);
    }
}