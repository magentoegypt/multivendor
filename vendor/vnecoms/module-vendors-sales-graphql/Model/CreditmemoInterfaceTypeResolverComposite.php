<?php

declare(strict_types=1);

namespace Vnecoms\VendorsSalesGraphQl\Model;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * @inheritdoc
 */
class CreditmemoInterfaceTypeResolverComposite implements TypeResolverInterface
{
    /**
     * @var array
     */
    private $creditMemoTypeNameResolvers = [];

    /**
     * InvoiceInterfaceTypeResolverComposite constructor.
     *
     * @param array $creditMemoTypeNameResolvers
     */
    public function __construct(array $creditMemoTypeNameResolvers = [])
    {
        $this->creditMemoTypeNameResolvers = $creditMemoTypeNameResolvers;
    }

    /**
     * @inheritdoc
     */
    public function resolveType(array $data): string
    {
        $resolvedType = null;
        foreach ($this->creditMemoTypeNameResolvers as $creditMemoTypeNameResolver) {
            $resolvedType = $creditMemoTypeNameResolver->resolveType($data);
            if (!empty($resolvedType)) {
                return $resolvedType;
            }
        }

        throw new GraphQlInputException(
            __('Concrete type for %1 not implemented', ['InvoiceInterface'])
        );
    }
}
