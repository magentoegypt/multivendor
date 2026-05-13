<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model\Resolver;

use Vnecoms\VendorsSalesGraphQl\Model\Shipment\CreateShipment as VendorCreateShipment;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GraphQl\Model\Query\ContextInterface;

/**
 * Customers field resolver, used for GraphQL request processing.
 */
class CreateVendorOrderShipment implements ResolverInterface
{
    /**
     * @var VendorCreateShipment
     */
    private $createShipment;

    /**
     * CreateVendorOrderShipment constructor.
     * @param VendorCreateShipment $createShipment
     */
    public function __construct(
        VendorCreateShipment $createShipment
    ) {
        $this->createShipment = $createShipment;
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

        if (!$args['vendor_order_id'] || !$args['items']) {
            throw new GraphQlInputException(__('Vendor order id and items must be not null.'));
        }

        return ["shipment" => $this->createShipment->execute($context, $args)];
    }
}