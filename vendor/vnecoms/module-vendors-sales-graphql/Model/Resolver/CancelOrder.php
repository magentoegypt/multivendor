<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model\Resolver;

use Vnecoms\VendorsSalesGraphQl\Model\Order\CancelOrder as VendorCancelOrder;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Customers field resolver, used for GraphQL request processing.
 */
class CancelOrder implements ResolverInterface
{
    /**
     * @var VendorCancelOrder
     */
    private $cancelOrder;

    /**
     * Vendor constructor.
     * @param VendorCancelOrder $cancelOrder
     */
    public function __construct(
        VendorCancelOrder $cancelOrder
    ) {
        $this->cancelOrder = $cancelOrder;
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

        if (!$args['order_id']) {
            throw new GraphQlInputException(__('Order id must be greater than 0.'));
        }

        return ["result" => $this->cancelOrder->execute($context, $args)];
    }
}