<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsGraphQl\Model\Resolver;

use Vnecoms\VendorsGraphQl\Model\Vendor\GetVendor;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\GraphQl\Model\Query\ContextInterface;
use Vnecoms\VendorsGraphQl\Model\Vendor\ExtractVendorData;

/**
 * Customers field resolver, used for GraphQL request processing.
 */
class Vendor implements ResolverInterface
{
    /**
     * @var GetVendor
     */
    private $getVendor;

    /**
     * @var ExtractVendorData
     */
    private $extractVendorData;

    /**
     * Vendor constructor.
     * @param GetVendor $getVendor
     * @param ExtractVendorData $extractVendorData
     */
    public function __construct(
        GetVendor $getVendor,
        ExtractVendorData $extractVendorData
    ) {
        $this->getVendor = $getVendor;
        $this->extractVendorData = $extractVendorData;
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

        $vendor = $this->getVendor->execute($context);
        return $this->extractVendorData->execute($vendor);
    }
}
