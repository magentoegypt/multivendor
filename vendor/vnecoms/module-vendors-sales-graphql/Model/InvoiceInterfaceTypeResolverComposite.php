<?php

declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * @inheritdoc
 */
class InvoiceInterfaceTypeResolverComposite implements TypeResolverInterface
{
    /**
     * @var array
     */
    private $invoiceTypeNameResolvers = [];

    /**
     * InvoiceInterfaceTypeResolverComposite constructor.
     *
     * @param array $invoiceTypeNameResolvers
     */
    public function __construct(array $invoiceTypeNameResolvers = [])
    {
        $this->invoiceTypeNameResolvers = $invoiceTypeNameResolvers;
    }

    /**
     * @inheritdoc
     */
    public function resolveType(array $data): string
    {
        $resolvedType = null;
        foreach ($this->invoiceTypeNameResolvers as $invoiceTypeNameResolver) {
            $resolvedType = $invoiceTypeNameResolver->resolveType($data);
            if (!empty($resolvedType)) {
                return $resolvedType;
            }
        }

        throw new GraphQlInputException(
            __('Concrete type for %1 not implemented', ['InvoiceInterface'])
        );
    }
}
