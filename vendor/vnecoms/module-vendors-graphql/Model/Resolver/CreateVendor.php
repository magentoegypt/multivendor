<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Vnecoms\VendorsGraphQl\Model\Resolver;

use Vnecoms\VendorsGraphQl\Model\Vendor\CreateVendorAccount;
use Vnecoms\VendorsGraphQl\Model\Vendor\ExtractVendorData;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Create customer account resolver
 */
class CreateVendor implements ResolverInterface
{
    /**
     * @var ExtractVendorData
     */
    private $extractVendorData;

    /**
     * @var CreateVendorAccount
     */
    private $createVendorAccount;

    /**
     * CreateVendor constructor.
     * @param ExtractVendorData $extractVendorData
     * @param CreateVendorAccount $createVendorAccount
     */
    public function __construct(
        ExtractVendorData $extractVendorData,
        CreateVendorAccount $createVendorAccount
    ) {
        $this->extractVendorData = $extractVendorData;
        $this->createVendorAccount = $createVendorAccount;
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
        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }

        $vendor = $this->createVendorAccount->execute(
            $args['input'],
            $context->getExtensionAttributes()->getStore()
        );

        $data = $this->extractVendorData->execute($vendor);
        return ['vendor' => $data];
    }
}
