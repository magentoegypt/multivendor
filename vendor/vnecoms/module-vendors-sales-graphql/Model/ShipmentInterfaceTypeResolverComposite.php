<?php

declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * @inheritdoc
 */
class ShipmentInterfaceTypeResolverComposite implements TypeResolverInterface
{
    /**
     * @var array
     */
    private $shipmentTypeNameResolvers = [];

    /**
     * ShipmentInterfaceTypeResolverComposite constructor.
     *
     * @param array $shipmentTypeNameResolvers
     */
    public function __construct(array $shipmentTypeNameResolvers = [])
    {
        $this->shipmentTypeNameResolvers = $shipmentTypeNameResolvers;
    }

    /**
     * @inheritdoc
     */
    public function resolveType(array $data): string
    {
        $resolvedType = null;
        foreach ($this->shipmentTypeNameResolvers as $shipmentTypeNameResolver) {
            $resolvedType = $shipmentTypeNameResolver->resolveType($data);
            if (!empty($resolvedType)) {
                return $resolvedType;
            }
        }

        throw new GraphQlInputException(
            __('Concrete type for %1 not implemented', ['ShipmentInterface'])
        );
    }
}
