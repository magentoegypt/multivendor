<?php
/**
 * Copyright © MagentoEgypt. All rights reserved.
 */
declare(strict_types=1);

namespace MagentoEgypt\HubAppReturns\Model\Resolver;

use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Who is asking and for which store view (the Store header), from the GraphQL context.
 */
final class Caller
{
    private function __construct()
    {
    }

    /**
     * The signed-in customer's id; GraphQlAuthorizationException without a customer token.
     *
     * @param ContextInterface|mixed $context
     * @throws GraphQlAuthorizationException
     */
    public static function customerId($context): int
    {
        $customerId = $context instanceof ContextInterface && $context->getExtensionAttributes()->getIsCustomer()
            ? (int) $context->getUserId()
            : 0;
        if ($customerId <= 0) {
            throw new GraphQlAuthorizationException(__('The current customer isn\'t authorized.'));
        }

        return $customerId;
    }

    /**
     * @param ContextInterface $context
     */
    public static function storeId($context): int
    {
        return (int) $context->getExtensionAttributes()->getStore()->getId();
    }

    /**
     * @param ContextInterface $context
     */
    public static function websiteId($context): int
    {
        return (int) $context->getExtensionAttributes()->getStore()->getWebsiteId();
    }
}
