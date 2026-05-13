<?php

declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * @inheritdoc
 */
class OrderInterfaceTypeResolverComposite implements TypeResolverInterface
{
    /**
     * @var array
     */
    private $orderTypeNameResolvers = [];

    /**
     * OrderInterfaceTypeResolverComposite constructor.
     *
     * @param array $orderTypeNameResolvers
     */
    public function __construct(array $orderTypeNameResolvers = [])
    {
        $this->orderTypeNameResolvers = $orderTypeNameResolvers;
    }

    /**
     * @inheritdoc
     */
    public function resolveType(array $data): string
    {
        $resolvedType = null;
        foreach ($this->orderTypeNameResolvers as $orderTypeNameResolver) {
            $resolvedType = $orderTypeNameResolver->resolveType($data);
            if (!empty($resolvedType)) {
                return $resolvedType;
            }
        }

        throw new GraphQlInputException(
            __('Concrete type for %1 not implemented', ['OrderInterface'])
        );
    }
}
