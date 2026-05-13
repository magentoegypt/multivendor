<?php

declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * @inheritdoc
 */
class OrderAddressInterfaceTypeResolverComposite implements TypeResolverInterface
{
    /**
     * @var array
     */
    private $orderAddressTypeNameResolvers = [];

    /**
     * OrderAddressInterfaceTypeResolverComposite constructor.
     *
     * @param array $orderAddressTypeNameResolvers
     */
    public function __construct(array $orderAddressTypeNameResolvers = [])
    {
        $this->orderAddressTypeNameResolvers = $orderAddressTypeNameResolvers;
    }

    /**
     * @inheritdoc
     */
    public function resolveType(array $data): string
    {
        $resolvedType = null;
        foreach ($this->orderAddressTypeNameResolvers as $orderTypeNameResolver) {
            $resolvedType = $orderTypeNameResolver->resolveType($data);
            if (!empty($resolvedType)) {
                return $resolvedType;
            }
        }

        throw new GraphQlInputException(
            __('Concrete type for %1 not implemented', ['OrderAddressInterface'])
        );
    }
}
