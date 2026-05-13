<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsNotificationGraphQl\Model\Resolver;

use Vnecoms\VendorsNotificationGraphQl\Model\Notification\MarkAllAsRead as MarkAllAsReadNotification;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Customers field resolver, used for GraphQL request processing.
 */
class MarkAllAsRead implements ResolverInterface
{
    /**
     * @var MarkAllAsReadNotification
     */
    private $markAllAsRead;

    /**
     * Vendor constructor.
     * @param MarkAllAsReadNotification $markAllAsRead
     */
    public function __construct(
        MarkAllAsReadNotification $markAllAsRead
    ) {
        $this->markAllAsRead = $markAllAsRead;
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

        return ["status" => $this->markAllAsRead->execute($context)];
    }
}