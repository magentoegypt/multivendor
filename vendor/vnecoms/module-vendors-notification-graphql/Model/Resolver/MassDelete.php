<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsNotificationGraphQl\Model\Resolver;

use Vnecoms\VendorsNotificationGraphQl\Model\Notification\MassDelete as NotificationDelete;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Customers field resolver, used for GraphQL request processing.
 */
class MassDelete implements ResolverInterface
{
    /**
     * @var NotificationDelete
     */
    private $delete;

    /**
     * Vendor constructor.
     * @param NotificationDelete $delete
     */
    public function __construct(
        NotificationDelete $delete
    ) {
        $this->delete = $delete;
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

        if (!$args['notification_ids']) {
            throw new GraphQlInputException(__('Notification id must be greater than 0.'));
        }

        return ["status" => $this->delete->execute($context, $args)];
    }
}